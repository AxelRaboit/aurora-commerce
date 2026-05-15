---
name: pref_commit_language
description: Les messages de commit doivent toujours être rédigés en anglais, jamais en français
metadata:
  type: user
---

## Règle

**Toujours rédiger les messages de commit en anglais**, quel que soit le contenu du changement.

## Pourquoi

Préférence explicite de l'utilisateur. L'historique git est destiné à être lisible universellement.

## Comment l'appliquer

Préfixes standardisés (`feat:`, `fix:`, `refactor:`, `docs:`, `test:`, `chore:`) suivis d'un résumé et d'un body en anglais. Ne jamais switcher en français même pour un commit "évident" ou une correction rapide.
