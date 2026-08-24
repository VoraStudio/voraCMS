# Proposal: Entry Form UX & Multilingual Refinement

## Summary
Millorar l'experiència d'usuari (UX), la consistència d'estats i la internacionalització completa del formulari de creació i edició d'entrades (`admin_entry_new` i `admin_entry_edit`).

## Motivation
- En crear una entrada (`new.html.twig`), el botó d'arxivar estava disponible tot i que no té sentit de negoci crear un contingut i arxivar-lo d'inici.
- Els textos dels interruptors de publicació mostraven incongruències quan estaven encesos o apagats.
- Els noms de camps estàndard (*Títol, Descripció, Imatge, Categoria, Data, Places, Preu, Ordre*) i els components auxiliars (*càrrega d'arxius, selectors de data, botons d'acció*) estaven en brut en català i no s'adaptaven en canviar d'idioma a ES o EN.
- Calia traduir el concepte 'Places' com a aforament/persones en anglès (*Capacity*).

## Scope
- Deshabilitar el toggle "Arxivat" a la pantalla de nova entrada (`new.html.twig`).
- Sincronitzar els estats de l'interruptor interactiu en JS amb el diccionari de traduccions de Symfony.
- Crear el filtre Twig `trans_field` a `AdminExtension.php` per traduir automàticament les etiquetes de camps comuns segons el `_locale` actiu.
- Traduir tots els elements estructurals: breadcrumbs, botó de tornada, botons inferiors (*Crear entrada*, *Guardar canvis*, *Cancel·lar*), components de fitxers i selectors d'imatges.
