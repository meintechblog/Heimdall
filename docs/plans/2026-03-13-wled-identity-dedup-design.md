# WLED Identity Dedup Design

## Goal

WLED-Discovery soll dasselbe physische Geraet auch dann als "bereits vorhanden" erkennen, wenn es in Heimdall schon ueber eine andere Adresse gespeichert ist, zum Beispiel ueber `wled-buero.local` statt `192.168.3.57`, oder ueber eine zweite IP von WLAN/LAN.

## Root Cause

Der aktuelle Discovery-Flow vergleicht nur den gefundenen Host gegen die bereits gespeicherten Item-Hosts. Dadurch entstehen falsche "neue" Treffer, sobald ein WLED-Geraet:

- ueber Friendly Name statt IP gespeichert ist
- ueber mehrere Netzwerkadressen erreichbar ist
- ueber Discovery auf einer anderen Adresse als im Item auftaucht

Zusaetzlich ist das lokal gespeicherte WLED-Icon beschaedigt.

## Chosen Approach

### Option 1: Host-only Vergleich

Einfach mehr Host-Varianten vergleichen, zum Beispiel `.local` gegen IP.

Warum nicht:
- zu fragil
- erkennt LAN/WLAN-Dubletten nicht sauber
- verlaesst sich auf Namensmuster statt Geraete-Identitaet

### Option 2: MAC-basierte Identitaet

WLED liefert in `/json/info` eine stabile `mac`. Discovery baut daraus eine Geraete-Identitaet und sammelt alle bekannten Alias-Adressen:

- aktuelle Discovery-IP
- mDNS-Name aus `/json/cfg`
- Friendly Name Hostvarianten wie `mdns.local`
- weitere durch Discovery beobachtete Hosts mit derselben `mac`

Bereits vorhandene Heimdall-Items werden gegen diese Identitaet gematcht. Das ist die gewaehlte Loesung.

Warum:
- robust gegen IP-Wechsel
- robust gegen LAN/WLAN-Doppeladressen
- update-faehig ohne DB-Schema-Aenderung

### Option 3: Extra Datenbanktabelle fuer Netzwerkidentitaeten

Technisch moeglich, aber fuer den aktuellen Bedarf zu schwergewichtig. Unnoetige Persistenz und Migrationsaufwand.

## Data Model

WLED-spezifische Metadaten werden im bestehenden `description`-JSON eines Items gespeichert:

```json
{
  "wled_identity": {
    "mac": "a8032aa13dd8",
    "mdns": "wled-buero2",
    "aliases": [
      "192.168.3.244",
      "wled-buero2.local"
    ]
  },
  "wled_preferred_url": "http://wled-buero2.local"
}
```

Das vermeidet eine Migration und bleibt kompatibel zum bestehenden Item-Config-Mechanismus.

## Edit UI

Auf der Item-Edit-Seite bekommt WLED eine kleine Zusatzsektion:

- bekannte WLED-Adresse(n) anzeigen
- eine Adresse als aktive URL auswaehlbar machen
- versteckte Config-Felder halten `wled_identity` und `wled_preferred_url` synchron

Die eigentliche `url` des Items wird auf die gewaehlte aktive Adresse gesetzt.

## Discovery Rules

- Mehrere Discovery-Treffer mit derselben `mac` werden zu einem Kandidaten zusammengefuehrt.
- Ein Kandidat wird unterdrueckt, wenn irgendein vorhandenes Item dieselbe WLED-Identitaet hat.
- Match-Regeln:
  - gleiche `mac`
  - oder vorhandene Item-URL trifft einen Alias des Kandidaten
  - oder vorhandene gespeicherte `wled_identity.aliases` treffen einen Alias des Kandidaten

## Verification

- Feature-Tests fuer:
  - Deduplizierung ueber `.local`
  - Deduplizierung ueber zweite IP
  - Zusammenfuehren mehrerer Discovery-Hosts zu einem Kandidaten
  - Speichern/Anzeigen der Alias-Liste im Edit-Formular
- Live-Pruefung:
  - WLED-Icon wieder korrekt
  - bekannte WLEDs erscheinen nicht mehr falsch im `+`
  - Edit-Seite zeigt Alias-Adressen und URL-Auswahl
