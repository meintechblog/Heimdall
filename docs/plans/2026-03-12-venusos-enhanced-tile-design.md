# VenusOS Enhanced Tile Design

## Goal

Einen neuen Heimdall-Anwendungstyp `VenusOS` bereitstellen, der fuer lokale Victron/Venus-OS-Systeme drei Live-Kennzahlen auf der Kachel anzeigt:

- PV-Produktion
- Akku-Stand in Prozent
- Netzübergabepunkt mit Richtung und Farbhinweis

Zielsystem ist aktuell `VenusOS Hallbude 3.11` unter `http://192.168.3.11`.

## Datenquelle

Die Implementierung nutzt den lokalen Victron-MQTT-Zugang auf Port `1883`.

Relevante Erkenntnisse aus der Analyse:

- Das Zielsystem bietet Plain MQTT auf `1883` an.
- Die lokale Portal-ID ist `dca6327406c5`.
- Per Keepalive auf `R/{portalId}/keepalive` werden aktuelle Werte erneut publiziert.
- Relevante Topics laut Victron-Dokumentation und Live-Probe:
  - `N/{portalId}/system/0/Dc/Pv/Power`
  - `N/{portalId}/system/0/Dc/Battery/Soc`
  - `N/{portalId}/system/0/Ac/Grid/L1/Power`
  - `N/{portalId}/system/0/Ac/Grid/L2/Power`
  - `N/{portalId}/system/0/Ac/Grid/L3/Power`

## UX

Die VenusOS-Kachel zeigt drei kompakte Zeilen:

- `PV`
  - unter `1000 W` als `W`
  - ab `1000 W` als `kW` mit einer Nachkommastelle
- `Battery`
  - Prozent mit einer Nachkommastelle, falls noetig
- `Grid`
  - aggregierte Leistung ueber alle veroeffentlichten Grid-Phasen
  - gruener Pfeil nach oben bei Einspeisung
  - roter Pfeil nach unten bei Netzbezug

Vorzeichen werden nicht roh angezeigt; Richtung wird ueber Pfeil und Farbe transportiert.

## Architektur

`VenusOS` wird als echter `Enhanced App`-Typ unter `app/SupportedApps/VenusOS` angelegt:

- `app.json`
- `VenusOS.php`
- `config.blade.php`
- `livestats.blade.php`

Die Live-Daten werden direkt in PHP ueber einen kleinen MQTT-Client ohne externe Zusatzbibliothek gelesen. Das vermeidet neue Composer-Abhaengigkeiten und passt zum bestehenden Overlay-Deploy.

Zusaetzlich wird eine kleine private App-Registrierung im Heimdall-DB-Bestand vorgesehen, damit `VenusOS` im Bearbeiten-/Erstellen-Formular auswählbar bleibt.

## Konfiguration

Die Konfiguration bleibt bewusst klein:

- `enabled`
- `override_url` optional
- `mqtt_port` mit Default `1883`
- `portal_id` optional

Wenn `portal_id` leer bleibt, versucht die App sie per kurzer MQTT-Wildcard-Abfrage selbst zu erkennen.

## Fehlerverhalten

Wenn keine sauberen Werte gelesen werden koennen:

- Tile-Status wird `inactive`
- HTML zeigt neutrale Fallback-Werte
- keine Exception soll den Dashboard-Aufruf zerlegen

`PV` darf `0` sein, auch wenn Victron `null` liefert.

## Update-Faehigkeit

Die Erweiterung bleibt update-faehig durch:

- Repo-verwaltete Dateien unter `app/SupportedApps/VenusOS`
- Tests fuer Aggregation/Formatierung
- Registrierungsschritt, der sich nach Deploy erneut anwenden laesst
- Dokumentation im Repo

## Upstream-Hinweis

Die Struktur orientiert sich an Heimdall-Enhanced-Apps. Fuer eine spaetere offizielle Meldung oder PR ist damit bereits eine saubere Basis vorhanden.
