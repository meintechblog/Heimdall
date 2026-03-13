# Hulki Maintenance

Die 3 wichtigsten Alltagsfälle für dieses Repo:

## 1. Nur live neu ausrollen

Wenn der Code auf `codex/heimdall-pimp` schon korrekt ist und nur erneut live eingespielt werden soll:

```bash
./scripts/hulki/replay-heimdall-overlay.sh
```

## 2. Heimdall-Basis aktualisieren und unsere Anpassungen behalten

Wenn upstream Heimdall auf `2.x` neue Änderungen hat:

```bash
./scripts/hulki/update-heimdall-custom-branch.sh
```

Das macht:

- `origin` holen
- `2.x` aktualisieren
- `codex/heimdall-pimp` auf `2.x` rebasen
- die wichtigsten Checks laufen lassen

## 3. Alles in einem Schritt aktualisieren und live deployen

```bash
./scripts/hulki/update-heimdall-custom-branch.sh --deploy
```

## Wichtige Regeln

- Nicht direkt im Live-Container herumeditieren.
- Änderungen immer im Repo machen und dann per Overlay deployen.
- Die Heimdall-LXC `CT100` sollte mindestens mit `2 Cores`, `1024 MB RAM` und `512 MB Swap` laufen.
- Die Live-CT sollte `nginx + php-fpm` nutzen, nicht `php artisan serve`.
- Für FileFlows-Pause/Resume nur die nativen Endpunkte verwenden.
- Nicht wieder `ui-settings` für `PausedUntil` missbrauchen, das hat auf der MacMini-Instanz `/initial-config` ausgelöst.
- Wenn eine FileFlows-Instanz nicht erreichbar ist, soll die Kachel nach wenigen Sekunden auf `Unavailable` fallen statt mit langem Timeout/`500` das Dashboard auszubremsen.
- Proxmox-Kacheln zeigen bewusst `Guests`, `CPU` und `RAM`, wobei `Guests` aus `VM + LXC` zusammengezählt wird.
- VenusOS-Kacheln lesen lokal per `MQTT on LAN (Plaintext)` auf Port `1883`; für `VenusOS Hallbude 3.11` ist die Portal-ID aktuell `dca6327406c5`.
- Der Live-Deploy registriert den privaten Anwendungstyp `VenusOS` automatisch erneut, falls Heimdall-Updates ihn aus der Datenbank werfen.
- Neue Live-Stat-Kacheln sollen sich an `docs/plans/2026-03-12-heimdall-tile-ci-design.md` orientieren, damit Spinner, Polling und Fallbacks gleich bleiben.
- Die Discovery scannt absichtlich gecacht im Hintergrund; wenn du das enger ziehen willst, setze `DISCOVERY_WLED_HOSTS` und `DISCOVERY_ESPRESENSE_HOSTS` auf feste Hostlisten statt das lokale `/24` abzutasten.
- WLED-Discovery dedupliziert ueber die WLED-`mac`, damit dasselbe Geraet nicht noch einmal ueber `.local`, WLAN-IP oder LAN-IP im `+` auftaucht.
- Auf der WLED-Edit-Seite wird die aktive URL jetzt aus den bekannten Alias-Adressen ausgewaehlt und wieder ins normale `url`-Feld geschrieben.

## Schnelle Live-Checks

```bash
curl -I http://192.168.3.88/
curl -I http://192.168.3.103:19200/
curl -I http://192.168.3.12:5000/
curl -s http://192.168.3.88/discoveries/summary
```

## Wichtige Detail-Doku

- allgemeiner Projektüberblick: `readme.md`
- Update-/Replay-Details: `docs/UPDATING_HULKI_FORK.md`
- Codex-Projektskills: `docs/CODEX_SKILLS.md`
