# Proposal: Login Multilingual & Legal Terms

## Summary
Afegir selector d'idioma a la pàgina de login i un modal desplegable amb les condicions legals i la política de privacitat adaptat a la identitat i arquitectura de VoraData i VoraStudio.

## Motivation
- Els usuaris han de poder escollir el seu idioma preferit (Català, Castellà o Anglès) abans d'iniciar sessió.
- Cal garantir la transparència legal respecte a la relació de servei entre VoraCMS, VoraData i VoraStudio, confirmant la no utilització de cookies de seguiment/publicitàries de tercers ni IA de tercers, i el tractament exclusiu de dades per a les landing pages i webs del client.

## Scope
- Afegir el component `s-lang-switcher` a la cantonada superior de [`templates/admin/login.html.twig`](file:///d:/webs/VoraDataCMS/templates/admin/login.html.twig).
- Afegir l'enllaç "Condicions legals & Política de privacitat" al peu de la targeta de login.
- Crear el modal legal desplegable accessible i multilingüe (`legal.*` a `messages.*.yaml`).
- Estilització fosca i neta a [`public/css/admin/login.css`](file:///d:/webs/VoraDataCMS/public/css/admin/login.css).
