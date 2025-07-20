# Band Manager

Dieses Projekt ist eine sehr einfache Webanwendung zur Verwaltung einer Band. Sie bietet grundlegende Funktionen für Kontakte, Setlisten, Buchungen und Rechnungen. Bandbezogene Einstellungen wie Bankdaten oder Absenderadresse lassen sich über ein Einstellungsmenü anpassen. Die Anwendung verwendet PHP mit MySQL und lässt sich so auch auf günstigen Webhosting-Paketen betreiben. Buchungen durchlaufen einen kleinen Workflow (Anfrage → Bestätigt → Abgeschlossen), erst abgeschlossene Buchungen können einer Rechnung zugeordnet werden. Außerdem lassen sich Musiker verwalten und pro Buchung hinterlegen.

## Einrichtung

1. Lege in MySQL eine Datenbank `bandmanager` an und importiere die Datei `schema.sql`:
   ```bash
   mysql -u USER -p bandmanager < schema.sql
   ```
2. Passe bei Bedarf die Datenbank-Zugangsdaten über Umgebungsvariablen an:
   - `DB_HOST` (Standard: `localhost`)
   - `DB_USER` (Standard: `root`)
   - `DB_PASS` (Standard: leer)
   - `DB_NAME` (Standard: `bandmanager`)
   - `APP_USER` (Standard: `admin`)
   - `APP_PASS` (Standard: `secret`)
3. Starte einen PHP-Webserver im Projektverzeichnis:
   ```bash
   php -S localhost:8000 -t public
   ```
4. Rufe im Browser `http://localhost:8000` auf.
   Du wirst per HTTP Basic Auth nach Benutzername und Passwort gefragt (`admin`/`secret`, sofern nicht über Umgebungsvariablen geändert).

Die Oberfläche basiert auf Bootstrap und ist somit mobilfreundlich.

## Projektstruktur

- `public/index.php` – HTML/JavaScript-Frontend (nutzt HTTP Basic Auth).
- `public/api.php` – Einfache API für CRUD-Operationen.
- `schema.sql` – Datenbankschema für MySQL (inkl. Tabellen für Buchungen, Musiker und Einstellungen).

Die Anwendung dient als kompakte Basis und kann nach Belieben erweitert werden.
