# Piège : `form.removeField()` de pdf-lib ne détache pas fiablement les widgets « merged »

## Règle

Quand un widget AcroForm doit être retiré d'un PDF (typiquement un champ
`PDFSignature` qu'on remplace par un dessin canvas), **ne pas se reposer
sur `form.removeField()`** de pdf-lib 1.17.1 si on prévoit ensuite
d'appeler `form.flatten()`.

À la place, faire le nettoyage à la main :

1. Collecter les refs candidates : `field.ref` **et** chaque `widget.ref`
   retourné par `field.acroField.getWidgets()` (pour les PDFs « split »
   où field-dict et widget-dict sont séparés).
2. Strip ces refs de `page.node.Annots()` pour **chaque page**.
3. Strip ces refs de `form.acroForm.dict.lookup(PDFName.of('Fields'))`.
4. Wrapper `form.flatten()` dans un `try/catch` comme dernière ceinture
   de sécurité — si un widget orphelin survit malgré tout, le PDF est
   tout de même saveable (le dessin direct sur la page est déjà fait).

Pattern d'implémentation de référence :
[`tools/pdf/fill.mjs::detachSignatureField()`](../../../../tools/pdf/fill.mjs).

## Pourquoi

`form.removeField()` traverse les pages à la recherche de `widget.ref`
pour décrocher le widget, mais sur une structure **merged widget+field**
(un seul `PDFRef` pour field et widget — c'est ce que produit Acrobat
par défaut), la comparaison de refs ne match pas comme attendu et le
widget reste attaché à `page.Annots`.

`form.flatten()` itère ensuite **tous les widgets** des fields restants
et appelle `getNormalAppearance()`. Sur un widget signature qui n'a pas
de stream `/AP/N` (cas normal — on n'a jamais signé numériquement, on a
juste dessiné un PNG sur la page), `getNormalAppearance()` lève
`Error: Unexpected N type: undefined` et crash le script entier.

Conséquence : sans ce cleanup manuel, **le `--flatten` plante toujours**
quand on a embed une signature canvas, même si le dessin a réussi.

## Comment l'appliquer

- Concerne `tools/pdf/fill.mjs` et tout futur script Node qui manipule
  des PDFs AcroForm dans Aurora.
- À retenir aussi si on extend PdfForm pour supporter multi-signature
  par PDF (V2) ou pour retirer d'autres types de widgets « visuels seuls »
  (tampons, watermarks dynamiques).
- Le piège est versionné : pdf-lib 1.17.1 (la version actuelle du repo).
  Vérifier avant de migrer à pdf-lib 2.x — peut-être corrigé upstream.
