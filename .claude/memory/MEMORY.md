# Aurora — index mémoire racine

Ce fichier est l'index de haut niveau. Les mémoires sont organisées par
périmètre :

- **[`aurora-core/`](aurora-core/MEMORY.md)** — conventions, décisions, pièges
  et préférences propres au bundle aurora-core (PHP/Symfony/Vue).
- **[`aurora-client/`](aurora-client/MEMORY.md)** — patterns d'extension côté
  consommateur. Distribués via composer : les clients les lisent depuis
  `vendor/axelraboit/aurora/.claude/memory/aurora-client/`.

> Toute nouvelle mémoire va dans le bon sous-dossier, jamais directement à
> la racine.
