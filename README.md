# Band Manager

Dieses Projekt ist eine sehr einfache Webanwendung zur Verwaltung einer Band. Sie bietet grundlegende Funktionen für Kontakte, Setlisten, Buchungen und Rechnungen. Bandbezogene Einstellungen wie Bankdaten oder Absenderadresse lassen sich über ein Einstellungsmenü anpassen. Die Anwendung verwendet PHP und kann je nach Einstellung MySQL oder SQLite nutzen und lässt sich so auch auf günstigen Webhosting-Paketen betreiben. Buchungen durchlaufen einen kleinen Workflow (Anfrage → Bestätigt → Abgeschlossen), erst abgeschlossene Buchungen können einer Rechnung zugeordnet werden. Außerdem lassen sich Musiker verwalten und pro Buchung hinterlegen.

## Einrichtung

1. Entscheide, ob du MySQL oder SQLite verwenden möchtest. Für MySQL lege eine
   Datenbank `bandmanager` an und importiere `schema.sql`:
   ```bash
   mysql -u USER -p bandmanager < schema.sql
   ```
   Bei Verwendung von SQLite importiere `schema.sqlite.sql` in eine neue Datei
   (z.B. `database.sqlite`):
   ```bash
   sqlite3 database.sqlite < schema.sqlite.sql
   ```
2. Kopiere `config.example.php` zu `config.php` und passe insbesondere
   `DB_DRIVER` ("mysql" oder "sqlite") sowie die zugehörigen Zugangsdaten an.
3. Starte einen PHP-Webserver im Projektverzeichnis:
   ```bash
   php -S localhost:8000 -t public
   ```
4. Rufe im Browser `http://localhost:8000` auf.
   Du wirst per HTTP Basic Auth nach Benutzername und Passwort gefragt (`admin`/`secret`, sofern nicht in der `config.php` angepasst).

Die Oberfläche basiert auf Bootstrap und ist somit mobilfreundlich.

## Projektstruktur

- `public/index.php` – HTML/JavaScript-Frontend (nutzt HTTP Basic Auth).
- `public/api.php` – Einfache API für CRUD-Operationen.
- `schema.sql` – Datenbankschema für MySQL.
- `schema.sqlite.sql` – Entsprechendes Schema für SQLite.

Die Anwendung dient als kompakte Basis und kann nach Belieben erweitert werden.
