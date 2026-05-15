---
name: pref_no_co_authored
description: Ne jamais ajouter Co-Authored-By Claude dans les messages de commit
metadata:
  type: user
---

## Règle

**Ne jamais ajouter** de ligne `Co-Authored-By: Claude …` dans les messages de commit, même implicitement via le footer généré par défaut.

## Pourquoi

Préférence explicite de l'utilisateur. Historique propre, attribution claire.

## Comment l'appliquer

Format de commit standard :

```
feat: short summary

Body explaining the why and what.
- bullet 1
- bullet 2
```

Pas de `🤖 Generated with [Claude Code]`, pas de `Co-Authored-By:`.
