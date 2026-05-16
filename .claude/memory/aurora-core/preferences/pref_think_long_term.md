---
name: pref-think-long-term
description: User prefers "design for the long term" over Claude's default YAGNI rule. Architectural refactors should happen as soon as the abstraction is sound, even without an immediate concrete user — within explicit guardrails.
metadata:
  type: feedback
---

Sur ce projet (aurora-core), la règle Claude par défaut **"Three similar
lines is better than a premature abstraction" / "Don't design for
hypothetical future requirements"** est **inversée**. L'utilisateur
préfère **anticiper les évolutions** plutôt qu'attendre qu'un besoin
force la refacto.

**Why:** Aurora-core est un bundle composer distribué à plusieurs
clients (aurora-client*). Une abstraction saine ajoutée maintenant =
extensibilité gratuite pour TOUS les clients futurs. Une abstraction
absente = chaque client qui en a besoin fork ou patch ad-hoc =
incohérence cross-clients à terme.

**How to apply:**

✅ **Faire le refactor MAINTENANT** quand l'abstraction est :
- Architecturalement saine (SOLID, separation of concerns,
  inversion de dépendances)
- Justifiée par un principe documenté (cf. `entity_extensibility_convention.md`,
  `storage_policy.md`, etc.)
- Cohérente avec ce qui existe ailleurs dans le repo (un 3e site
  similaire = seuil pour extraire un helper, cf. `Num::clamp`)
- Préparant l'extensibilité aurora-client (le cœur du projet)

❌ **Ne PAS faire si** :
- Aucun implémenteur multiple plausible (interface sans 2nd impl =
  fluff)
- Hook sans usecase d'override identifié (méthode `protected` qui ne
  sera jamais surchargée = bruit)
- Coût disproportionné (refacto 50 fichiers pour une amélioration
  marginale)
- Spéculation pure ("et si plus tard…" sans pattern SOLID à invoquer)

**Exemples concrets validés** :
- 22 callers de `Media::getPublicUrl()` → refactor vers
  `UrlGeneratorInterface` injecté (séparation domaine/HTTP) ✅
- `Num::clamp` extrait dès 10+ sites inline ✅
- Convention extensibilité Sylius 5 couches partout ✅
- Settings admin pour 2 réglages d'image notes-markdown (1 client) ✅

**Exemples qui ont été refusés** :
- Interface `MarkdownNoteImageServiceInterface` (1 service stateless) ❌
- Sous-dossiers dans `shared/components/overlay/` (4 composants) ❌

Cette philosophie est documentée dans `CLAUDE.md` §3bis. Toute
nouvelle convention validée par l'utilisateur **peut** être actée
comme mémoire ou doc, mais ce pref-là reste le défaut Claude pour
toutes les sessions sur ce repo.
