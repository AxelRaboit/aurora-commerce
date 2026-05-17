# Mise en place du pipeline OCR — Aurora

## Architecture

```
Navigateur
    │  upload PDF/image
    ▼
Symfony (HTTP)
    │  crée OcrJob + dispatch message
    ▼
Symfony Messenger Worker  ◄── ProcessOcrJobMessage
    │
    ├─► docTR microservice (Python)
    │       extraction texte + layout → pages[], text
    │
    └─► Ollama (vision model)
            image + texte OCR → JSON structuré (InvoiceDraft)
                │
                ▼
            Invoice créée/mise à jour en base
```

## Services requis

| Service | Rôle | Port défaut |
|---------|------|-------------|
| **docTR** | Extraction texte/layout PDF et images | `8001` |
| **Ollama** | Inférence du modèle vision | `11434` |
| **Symfony Worker** | Traitement async des jobs OCR | — |

---

## 1. docTR microservice

### Installation

```bash
pip install python-doctr[torch] fastapi uvicorn python-multipart Pillow pdf2image
```

Le microservice doit exposer :
- `POST /extract` — multipart `file` → `{ pages: [...], text: "..." }`
- `POST /render` — multipart `file` + `dpi` (int) → PNG binaire (pour les PDFs)

### Lancement dev

```bash
uvicorn doctr_service:app --host 0.0.0.0 --port 8001
```

> **Note prod :** exécuter derrière un reverse proxy (apache2), superviser avec systemd ou supervisord. Prévoir 2–4 workers uvicorn selon la charge.

---

## 2. Ollama

### Installation

```bash
curl -fsSL https://ollama.ai/install.sh | sh
```

### Téléchargement du modèle vision

```bash
ollama pull qwen2.5vl:3b      # modèle par défaut — rapide, bon sur factures
```

Modèles testés et disponibles :

| Modèle | Taille | Vitesse (GPU) | Recommandé pour |
|--------|--------|---------------|-----------------|
| `qwen2.5vl:3b` | 3.2 GB | ~15–30 s | **Défaut** — meilleur rapport qualité/vitesse |
| `minicpm-v` | 5.5 GB | ~30–60 s | Précision accrue sur documents complexes |
| `qwen3-vl` | 6.1 GB | ~2–5 min | Haute précision, nécessite ≥ 12 GB VRAM |
| `llava:7b` | 4.7 GB | ~30–60 s | Généraliste, moins précis sur factures |

Changer de modèle : mettre à jour `OLLAMA_VISION_MODEL` dans `.env`.

### Lancement

```bash
ollama serve   # démarre sur 0.0.0.0:11434
```

> **Note prod :** Ollama bénéficie largement d'un **GPU** (NVIDIA avec CUDA, ou Apple Silicon).
> Sans GPU, l'inférence prend 5–15 min par facture.
> Avec GPU RTX 5070+ : 15–60 s selon le modèle.

---

## 3. Symfony Messenger Worker

### Dev (Doctrine transport, SQLite/PostgreSQL)

Le transport `doctrine://default` est configuré par défaut — aucune infrastructure supplémentaire.

```bash
php bin/console messenger:consume async --time-limit=3600 -vv
```

> Redémarrer le worker après chaque déploiement de code.

### Prod (Redis ou RabbitMQ recommandé)

Dans `.env.local` :

```dotenv
# Redis
MESSENGER_TRANSPORT_DSN=redis://localhost:6379/messages

# RabbitMQ
MESSENGER_TRANSPORT_DSN=amqp://user:pass@localhost:5672/%2f/messages
```

Supervision avec **Supervisor** :

```ini
[program:aurora-worker]
command=php /var/www/aurora/bin/console messenger:consume async --time-limit=3600
directory=/var/www/aurora
numprocs=2
autostart=true
autorestart=true
stderr_logfile=/var/log/aurora/worker.log
```

---

## 4. Variables d'environnement

### Fichier `.env` (valeurs dev)

```dotenv
OCR_DOCTR_URL=http://localhost:8001
OLLAMA_URL=http://localhost:11434
OLLAMA_VISION_MODEL=qwen2.5vl:3b   # voir tableau des modèles ci-dessus
OCR_HTTP_TIMEOUT=600               # secondes — augmenter sans GPU
OCR_RENDER_DPI=200                 # résolution du rendu PDF → PNG (200–300)
OCR_NUM_CTX=12288                  # contexte Ollama (tokens entrée + sortie)
OCR_NUM_PREDICT=-1                 # -1 = pas de limite sur la sortie générée
```

### Paramètre `OCR_RENDER_DPI`

| Valeur | Usage |
|--------|-------|
| `150` | Documents haute résolution déjà nets |
| `200` | **Défaut** — bon équilibre qualité/taille |
| `300` | Factures très denses ou petite police |

> Monter le DPI augmente la taille de l'image envoyée au modèle vision et donc le temps d'inférence.

---

## 5. Ce qui est implémenté dans Aurora

### Pipeline complet

1. **Upload** (JPEG, PNG, WebP, PDF) via `/admin/billing/ocr/import`
2. **docTR** : extraction texte + layout de toutes les pages
3. **Rendu image** : si PDF, conversion en PNG au DPI configuré
4. **Ollama VLM** : analyse de l'image + texte OCR → JSON structuré
5. **InvoiceDraft** : DTO typé avec tous les champs facture
6. **Création/MAJ facture** : `InvoiceManager::createFromOcrDraft()`
7. **Tiers auto** : fournisseur et acheteur créés/trouvés dans `billing_tiers`
8. **Scoring** : confiance globale + liste des champs incertains
9. **Statut** : `completed` (≥ 85% et totaux cohérents) ou `needs_review`

### Champs extraits automatiquement

**Fournisseur / Acheteur :** nom, TVA, SIRET, IBAN, BIC, email, téléphone, adresse, pays, site web, forme juridique, banque

**Facture :** numéro, référence interne, N° commande, dates (émission, échéance, livraison), conditions/mode de règlement, devise, Incoterms, autoliquidation TVA, coordonnées bancaires

**Montants :** sous-total HT, remise (montant + taux), frais de port, assurance, total HT, TVA, total TTC

**Lignes :** libellé, code produit, référence article, description, quantité, unité, PU HT, remise ligne, TVA, total HT, total TTC, pays d'origine

### Logs en temps réel

Le pipeline loggue chaque étape dans `OcrJob.logs` (consultable en direct depuis l'UI) :
- Fichier source (nom, type, taille)
- Résultat docTR (pages, caractères, aperçu texte)
- Résultat VLM (confiance, lignes, résumé des champs extraits)
- Anomalies détectées (champs incertains, écart de totaux)

---

## 6. Checklist prod

- [ ] docTR déployé et accessible depuis le serveur Symfony
- [ ] Ollama déployé avec le modèle vision téléchargé (`ollama pull qwen2.5vl:3b`)
- [ ] GPU disponible pour Ollama (fortement recommandé)
- [ ] `MESSENGER_TRANSPORT_DSN` pointant sur Redis ou RabbitMQ
- [ ] Worker supervisé (Supervisor ou systemd), ≥ 2 processus
- [ ] `OCR_HTTP_TIMEOUT` suffisant (≥ 300s sans GPU, ≥ 60s avec GPU)
- [ ] `OCR_NUM_CTX=12288` et `OCR_NUM_PREDICT=-1` configurés
- [ ] `OCR_RENDER_DPI=200` (ajuster selon qualité des documents)
- [ ] Vérifier que `var/cache/ocr/` est accessible en écriture (cache des PNG)
- [ ] Vérifier que `var/uploads/ocr/` est accessible en écriture (fichiers uploadés — hors document root, convention `var/uploads/`)
- [ ] Redémarrer le worker après chaque déploiement

---

## 7. Dépannage courant

| Symptôme | Cause probable | Action |
|----------|---------------|--------|
| Job bloqué en `Queued` | Worker non démarré | `messenger:consume async` |
| Erreur docTR transport | Service injoignable | Vérifier `OCR_DOCTR_URL` et que le service tourne |
| Confiance toujours < 50% | Modèle trop conservateur ou image dégradée | Monter `OCR_RENDER_DPI`, tester `minicpm-v` |
| Réponse JSON vide | `OCR_NUM_CTX` trop bas (prompt + image déborde) | Augmenter `OCR_NUM_CTX` à 12288 ou 16384 |
| `uncertain_fields` avec libellés lisibles | Modèle confond clé schema et texte facture | Problème connu, corrigé dans le prompt |
| Timeout Ollama | Inférence trop lente ou modèle bloqué | Redémarrer `ollama serve`, utiliser `qwen2.5vl:3b` |
| `InvoiceDraft __construct` erreur | Worker avec ancien bytecode | Redémarrer le worker |
| Job bloqué en `parsing` des heures | Ollama planté (souvent après restart worker) | Redémarrer Ollama, remettre le job en `queued` |
