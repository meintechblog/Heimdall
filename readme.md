# Heimdall

[![Heimdall_Banner](https://i.imgur.com/iuV8w3y.png)](https://heimdall.site)

[![Discord](https://img.shields.io/discord/354974912613449730.svg)](https://discord.gg/CCjHKn4)
[![Docker Pulls](https://img.shields.io/docker/pulls/linuxserver/heimdall.svg)](https://hub.docker.com/r/linuxserver/heimdall/)
[![firsttimersonly](https://img.shields.io/badge/first--timers--only-friendly-blue.svg)](https://www.firsttimersonly.com/)
[![Paypal](https://heimdall.site/img/paypaldonate.svg)](https://www.paypal.me/heimdall)

___

Visit the website - https://heimdall.site
___

## About
As the name suggests Heimdall Application Dashboard is a dashboard for all your web applications. It doesn't need to be limited to applications though, you can add links to anything you like.

Heimdall is an elegant solution to organise all your web applications. It’s dedicated to this purpose so you won’t lose your links in a sea of bookmarks.

Why not use it as your browser start page? It includes a search bar that filters Heimdall tiles live while you type and opens a Google search in a new tab when you press `Enter`.

![Heimdall demo animation](https://i.imgur.com/MrC4QpN.gif)

## Video
If you want to see a quick video of Heimdall in use, go to https://youtu.be/GXnnMAxPzMc

## Supported applications
You can use the app to link to any site or application, but Foundation apps will auto fill in the icon for the app and supply a default color for the tile. In addition, Enhanced apps allow you provide details to an apps API, allowing you to view live stats directly on the dashboard. For example, the NZBGet and Sabnzbd Enhanced apps will display the queue size, and download speed while something is downloading.

Supported applications are recognized by the title of the application as entered in the title field when adding an application. For example, to add a link to pfSense, begin by typing "p" in the title field and then select "pfSense" from the list of supported applications.

[![enhancedapps](https://img.shields.io/badge/dynamic/json.svg?label=Enhanced%20Apps&url=https%3A%2F%2Fapps.heimdall.site%2Fstats&query=enhanced_apps&colorB=3f8483&style=for-the-badge&logo=data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABwAAAAjCAMAAACw/5reAAAAnFBMVEUAAADu7u7u7u7u7u7u7u7x8fHu7u7u7u7u7u7u7u7u7u7u7u7r6+vu7u7v7+/u7u7t7e3v7+/v7+/u7u7u7u7u7u7u7u7u7u7u7u7u7u7v7+/u7u7p6ent7e3v7+/v7+/v7+/u7u7u7u7u7u7u7u7t7e3////u7u7u7u7u7u7u7u7w8PDw8PDt7e3u7u7t7e3s7Ozu7u7t7e3u7u4TnCP6AAAAM3RSTlMA+9n3phHw3czC088M5Y5zG6mflWdJFumyfj4sB2NeTi7hiWlDOQPGt5lsMiG9hFQntpFqxQJtAAABnElEQVQoz2WRh3KrQAxFtYWO6ZhucItrynv6/3/LFnA24c6wurpnYBkJZvXduNix6+GXTo8qWnxUPU4m2w0O1ktTozPsftiZpejGlm7C2MWUnRcWOohIo36+PaKyDZdLUOgDXvqQfaT9kwkfvP3AN18E7Kl8hkJHMHSXSSadxaTtTNjJhMkfjFHKMqGlolg4T7mtCbcq8gBCotxkwklFLIQSlQoTHnVWQqzNxYQuzpfmqGVMc5ijHK5yAuIhxbZ5p/S92RZkjv5BKs6aosSIr0JrcXBo1FtICVINKRKK6u0GnraoN84O5KbhjRwYzxCJnQCMtotkdNxjq2F7dJ2RoGuXIBTvc3ROthdmat6hZ7cOyfcxKGV+wTxBkxQxTQTzWOFny/7qS2nzx37T7nbtZj9xu7zUr/323nVy0sQnhwMJktSZrl5v7CjgSQmWi+haUCY8sH4tyc/FGSKGouS+WqBJm8U2NIE/+nLu2tzpF/xVNGy02QzRClafC/ysVpDzQJuA8xXsKl8bv+pgpXz57H9Yy3J1lQNY62wUrW+mdzrylWS0QwAAAABJRU5ErkJggg==)](https://apps.heimdall.site/applications/enhanced)

[![foundationapps](https://img.shields.io/badge/dynamic/json.svg?label=Foundation%20Apps&url=https%3A%2F%2Fapps.heimdall.site%2Fstats&query=foundation_apps&colorB=3f8483&style=for-the-badge&logo=data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABwAAAAjCAMAAACw/5reAAAAnFBMVEUAAADu7u7u7u7u7u7u7u7x8fHu7u7u7u7u7u7u7u7u7u7u7u7r6+vu7u7v7+/u7u7t7e3v7+/v7+/u7u7u7u7u7u7u7u7u7u7u7u7u7u7v7+/u7u7p6ent7e3v7+/v7+/v7+/u7u7u7u7u7u7u7u7t7e3////u7u7u7u7u7u7u7u7w8PDw8PDt7e3u7u7t7e3s7Ozu7u7t7e3u7u4TnCP6AAAAM3RSTlMA+9n3phHw3czC088M5Y5zG6mflWdJFumyfj4sB2NeTi7hiWlDOQPGt5lsMiG9hFQntpFqxQJtAAABnElEQVQoz2WRh3KrQAxFtYWO6ZhucItrynv6/3/LFnA24c6wurpnYBkJZvXduNix6+GXTo8qWnxUPU4m2w0O1ktTozPsftiZpejGlm7C2MWUnRcWOohIo36+PaKyDZdLUOgDXvqQfaT9kwkfvP3AN18E7Kl8hkJHMHSXSSadxaTtTNjJhMkfjFHKMqGlolg4T7mtCbcq8gBCotxkwklFLIQSlQoTHnVWQqzNxYQuzpfmqGVMc5ijHK5yAuIhxbZ5p/S92RZkjv5BKs6aosSIr0JrcXBo1FtICVINKRKK6u0GnraoN84O5KbhjRwYzxCJnQCMtotkdNxjq2F7dJ2RoGuXIBTvc3ROthdmat6hZ7cOyfcxKGV+wTxBkxQxTQTzWOFny/7qS2nzx37T7nbtZj9xu7zUr/323nVy0sQnhwMJktSZrl5v7CjgSQmWi+haUCY8sH4tyc/FGSKGouS+WqBJm8U2NIE/+nLu2tzpF/xVNGy02QzRClafC/ysVpDzQJuA8xXsKl8bv+pgpXz57H9Yy3J1lQNY62wUrW+mdzrylWS0QwAAAABJRU5ErkJggg==)](https://apps.heimdall.site/applications/foundation)

## Installing
Apart from the Laravel 10 dependencies, namely PHP >= 8.1, Ctype PHP Extension, cURL PHP Extension, DOM PHP Extension, Fileinfo PHP Extension, Filter PHP Extension, Hash PHP Extension, Mbstring PHP Extension, OpenSSL PHP Extension, PCRE PHP Extension, PDO PHP Extension, Session PHP Extension, Tokenizer PHP Extension, XML PHP Extension, the only other thing Heimdall needs is sqlite support and zip support (php-zip).

If you find you can't change the background make sure `php_fileinfo` is enabled in your php.ini. I believe `php_fileinfo` should be enabled by default, but one user came across the issue on a windows system.

Installation is as simple as cloning the repository somewhere, or downloading and extracting the zip/tar and pointing your httpd document root to the `/public` folder then creating the .env file and generating an encryption key (this is all taken care of for you with the docker). 

```
cd /path/to/heimdall
cp .env.example .env
php artisan key:generate
```

For simple testing you could just go to the folder and type `php artisan serve`

There is also a multi-arch Docker which supports x86-64, armhf and arm64, instructions on how to use them at

- https://hub.docker.com/r/linuxserver/heimdall/

## Updating
To update your instance, simply clone this repository or download the zip/tar file with the new version and copy it over the old installation.

### Hulki fork workflow
For the Hulki custom fork, use the repo-managed replay workflow in `docs/UPDATING_HULKI_FORK.md`. It covers rebasing the customization branch onto `2.x`, rebuilding assets, exporting the live overlay from the repo with stale-asset protection, syncing the host copy on `proxi1`, and redeploying into CT `100`.

For the short day-to-day command list, use `MAINTENANCE.md`.

For Codex-specific reusable project skills, see `docs/CODEX_SKILLS.md`.

### Hulki custom features
This fork also carries a small FileFlows dashboard extension for the two live FileFlows tiles:

- the tile still opens FileFlows when you click the normal tile area
- the logo area acts as a direct pause/resume toggle
- the visible icon reflects the current FileFlows processing state

For long-lived pause/resume, the Hulki fork intentionally uses the native FileFlows pause API:

- pause: `POST /api/system/pause?duration=52560000`
- resume: `POST /api/system/pause?duration=0`

This is deliberate. A previous attempt to drive pause/resume through FileFlows `ui-settings` caused at least one live instance to redirect to `/initial-config`, so the fork now avoids that path.

This fork also carries a repo-managed Proxmox enhanced-tile customization:

- the Proxmox app config is available even before live API details have been saved
- the tile can use token-based Proxmox API access with optional URL override, node filtering, and optional TLS-skip mode
- the live tile stats focus on `Guests`, `CPU`, and `RAM`
- the tile uses the shared left-icon loading overlay pattern used by the custom live-stat tiles

This fork also carries a private `VenusOS` enhanced app for local Victron/Venus devices:

- the tile reads local Venus metrics over LAN MQTT
- the live tile stats focus on `PV`, `Battery`, and `Grid`
- `Grid` uses a green up-arrow for export and a red down-arrow for import
- power values stay in `W` below `1000 W` and switch to `kW` with one decimal place above that

This fork also carries a cached multi-source auto-discovery flow on the dashboard:

- the dashboard checks in the background for new `WLED`, `ESPresense`, `VenusOS`, `Shelly`, `AWTRIX`, `Mobotix`, `NodeRED`, `go2rtc`, `openWB`, `openDTU`, `Homebridge`, and `Home Assistant` services that are not already present in the item list
- the `+` button sits directly next to the search field and only appears when unmatched discovery candidates exist
- clicking `+` opens prepared discovery tiles directly below the search field
- when a fresh discovery run starts, candidates now appear progressively while the panel is still searching
- clicking a discovery card opens the device itself
- clicking `Hinzufuegen` creates a normal Heimdall item
- WLED candidates prefer the configured `mDNS` identifier from `/json/cfg` as the suggested title
- WLED candidates are deduplicated by device identity (`mac`), so `.local`, WLAN IP, and LAN IP variants of the same controller collapse into one device
- already-known WLED items are suppressed even when Heimdall currently stores them under a friendly hostname like `wled-buero.local`
- WLED items store discovered alias addresses in the item config so the edit page can offer a concrete active URL selection
- ESPresense candidates prefer the configured `room` value from `/json/info` as the suggested title
- VenusOS candidates are detected from the local Victron/Venus web markers and add real `VenusOS` tiles with the expected MQTT config shape
- Shelly candidates prefer the device `name` from `/settings` and add real `Shelly` tiles instead of generic links
- AWTRIX candidates are detected from `/api/stats` via the `awtrix_*` device UID marker and add normal Heimdall link tiles
- Mobotix candidates are detected from the root redirect to `/control/userimage.html` and add normal Heimdall link tiles
- NodeRED candidates are detected from `:1880/settings`
- go2rtc candidates are detected from `:1984/api`
- openWB candidates are detected from the fast root-page markers (`openWB_logo.svg` + `openWB Pro`)
- openDTU candidates are detected from `/api/livedata/status`
- Homebridge candidates are detected from the Homebridge web UI on `:8581`
- Home Assistant candidates are detected from the local API on `:8123`
- these service-style sources compare by full base URL including port, so multiple services on the same IP do not hide each other
- discovery is intentionally cache-backed, chunked, and tab-visibility-aware so it does not keep hammering the LAN while the dashboard is hidden

Optional tuning for discovery:

- `DISCOVERY_WLED_HOSTS=192.168.3.50,192.168.3.60` to limit scanning to known hosts instead of the whole local `/24`
- `DISCOVERY_ESPRESENSE_HOSTS=192.168.3.239,192.168.3.240` to limit ESPresense scanning to known hosts instead of the whole local `/24`
- `DISCOVERY_VENUSOS_HOSTS=192.168.3.11,192.168.3.14` to limit VenusOS scanning to known GX hosts instead of the whole local `/24`
- `DISCOVERY_SHELLY_HOSTS=192.168.3.40,192.168.3.56` to limit Shelly scanning to known hosts instead of the whole local `/24`
- `DISCOVERY_AWTRIX_HOSTS=192.168.3.141,192.168.3.154` to limit AWTRIX scanning to known displays instead of the whole local `/24`
- `DISCOVERY_MOBOTIX_HOSTS=192.168.3.21,192.168.3.24` to limit Mobotix scanning to known cameras instead of the whole local `/24`
- `DISCOVERY_NODERED_HOSTS=192.168.3.8,192.168.3.30,192.168.3.34` to limit NodeRED scanning to known hosts instead of the whole local `/24`
- `DISCOVERY_GO2RTC_HOSTS=192.168.3.125,192.168.3.174,192.168.3.219` to limit go2rtc scanning to known hosts instead of the whole local `/24`
- `DISCOVERY_OPENWB_HOSTS=192.168.3.119,192.168.3.156` to limit openWB scanning to known hosts instead of the whole local `/24`
- `DISCOVERY_OPENDTU_HOSTS=192.168.3.98` to limit openDTU scanning to known hosts instead of the whole local `/24`
- `DISCOVERY_HOMEBRIDGE_HOSTS=192.168.3.7,192.168.3.192` to limit Homebridge scanning to known hosts instead of the whole local `/24`
- `DISCOVERY_HOMEASSISTANT_HOSTS=192.168.3.175` to limit Home Assistant scanning to known hosts instead of the whole local `/24`
- `DISCOVERY_WLED_CACHE_TTL_SECONDS=900` to control how long WLED candidates stay cached
- `DISCOVERY_ESPRESENSE_CACHE_TTL_SECONDS=900` to control how long ESPresense candidates stay cached
- `DISCOVERY_VENUSOS_CACHE_TTL_SECONDS=900` to control how long VenusOS candidates stay cached
- `DISCOVERY_SHELLY_CACHE_TTL_SECONDS=900` to control how long Shelly candidates stay cached
- `DISCOVERY_AWTRIX_CACHE_TTL_SECONDS=900` to control how long AWTRIX candidates stay cached
- `DISCOVERY_MOBOTIX_CACHE_TTL_SECONDS=900` to control how long Mobotix candidates stay cached
- `DISCOVERY_NODERED_CACHE_TTL_SECONDS=900` to control how long NodeRED candidates stay cached
- `DISCOVERY_GO2RTC_CACHE_TTL_SECONDS=900` to control how long go2rtc candidates stay cached
- `DISCOVERY_OPENWB_CACHE_TTL_SECONDS=900` to control how long openWB candidates stay cached
- `DISCOVERY_OPENDTU_CACHE_TTL_SECONDS=900` to control how long openDTU candidates stay cached
- `DISCOVERY_HOMEBRIDGE_CACHE_TTL_SECONDS=900` to control how long Homebridge candidates stay cached
- `DISCOVERY_HOMEASSISTANT_CACHE_TTL_SECONDS=900` to control how long Home Assistant candidates stay cached
- `DISCOVERY_SUMMARY_REFRESH_SECONDS=300` to control how often the dashboard rechecks the cached discovery summary

Shared Heimdall tile design rules for the Hulki fork are documented in `docs/plans/2026-03-12-heimdall-tile-ci-design.md`. Use that as the baseline when adding new live-stat tile extensions so loading behavior, polling discipline, and icon-area feedback stay consistent.

To replay the Hulki customizations quickly after a Heimdall update, use:

```bash
./scripts/hulki/update-heimdall-custom-branch.sh
./scripts/hulki/replay-heimdall-overlay.sh
```

Or run the full update + deploy flow in one go:

```bash
./scripts/hulki/update-heimdall-custom-branch.sh --deploy
```

## Homepage Search
The homepage search field has two behaviors:

- typing filters Heimdall tiles on the dashboard live
- pressing `Enter` with a non-empty query opens a Google search in a new tab

In `categories` mode, filtered/search results reuse the original tile nodes instead of cloned duplicates, so duplicate category membership does not render duplicate tiles and live stats keep updating.

The dashboard tile filter UI lives in `/resources/assets/js/dashboardFilters.js`.

Regression coverage for this behavior:

- `npm run test:js`
- `php artisan test tests/Feature/ItemCreateTest.php tests/Feature/DashTest.php tests/Feature/ProxmoxLiveStatsTest.php`

## New background image not being set
If you are using the docker image or a default php install you may find images over 2MB won't get set as the background image, you just need to change the `upload_max_filesize` in the php.ini.

If you are using the linuxserver.io docker image simply edit `/path/to/config/php/php-local.ini` and add `upload_max_filesize = 30M` to the end.

## Docker and enhanced apps
If you are running the docker and the EnhancedApps you are using are also in dockers, you may need to use the docker networking addresses to communicate with them.

You can do this by using `http(s)://docker_name:port` in the config section. Instead of the name you can use the internal docker ip, this usually starts with `172.`

## Languages
The app has been translated into several languages; however, the quality of the translations could benefit from some work. If you would like to improve them, or help with other translations, they are stored in `/resources/lang/`.

To create a new language translation, make a new folder with the ISO 3166-1 alpha-2 code as the name, copy `app.php` from `/resources/lang/en/app.php` into your new folder and replace the English strings.

When you are finished, create a pull request.

Currently added languages are

- Breton
- Chinese
- Danish
- Dutch
- English
- Finnish
- French
- German
- Greek
- Hungarian
- Italian
- Japanese
- Korean
- Lombard
- Norwegian
- Polish
- Portuguese
- Russian
- Slovenian
- Spanish
- Swedish
- Turkish

## Web Server Configuration

### Apache
A `.htaccess` file ships with the app, however, a lot of apache installations disallow `.htaccess` files by default.
You will notice this due to some links not working like `/settings`.
In addition mod-rewrite needs to be enabled if it isn't already.

#### Fixes & work around options
##### - Apache global allow .htaccess
Find the `AllowOverride None` line in your apache configuration and change this to `AllowOverride All`

##### - Apache vhost configuration allow .htaccess
In the apache vhost configuration in the `<Directory />` block add `AllowOverride All`

##### - Add .htaccess content in apache configuration
You can add the full `.htaccess` into your apache configuration, this way you do not need to allow `.htaccess` files.
You can even shorten the content of the `.htaccess` when inserting it into the apache configuration to:
```
Options +FollowSymLinks
RewriteEngine On

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]
```
#### More info
More info about `AllowOverride` can be found here:
https://httpd.apache.org/docs/2.4/mod/core.html#allowoverride



### Nginx
If you are using Nginx, the following directive in your site configuration will direct all requests to the `index.php` front controller:

```
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```
Someone was using the same Nginx setup to both run this and reverse proxy Plex. Plex is served from `/web` so their location was interfering with the `/webfonts`.

Therefore, if your fonts aren't showing because you have a location for `/web`, add the following:
```
location /webfonts {
    try_files $uri $uri/;
}
```
If there are any other locations that might interfere with any of the folders in the `/public` folder, you might have to do the same for those as well, however it's a super fringe case.

### Reverse proxy
If you'd like to reverse proxy this app, we recommend using our letsencrypt/nginx docker image: [SWAG - Secure Web Application Gateway](https://hub.docker.com/r/linuxserver/swag)
You can either reverse proxy from the root location, or from a subdomain (subfolder method is currently not supported). For HTTPS proxy, make sure you use the HTTPS port of Heimdall webserver, otherwise some links may break. You can add security through `.htpasswd`

```
location / {
    auth_basic "Restricted";
    auth_basic_user_file /config/nginx/.htpasswd;
    include /config/nginx/proxy.conf;
    proxy_set_header X-Forwarded-Proto https;
    proxy_pass http://heimdall;
}
```

### Self-signed certificates and local CAs
Per default Heimdall uses the standard certificate bundle file (`ca-certificates.crt`) to verify HTTPS sites and will ignore additional certificates placed in `/etc/ssl/certs`. If you wish to use enhanced apps with HTTPS sites that use a self-signed certificate or certs signed with your own local CA, you can override the default bundle:

- Create a unified certificate `.pem` file that contains all CAs and certificates that Heimdall has to verify. For example, if you use both LetsEncrypt and a local CA for your internal apps, concatenate the LetsEncrypt intermediate CA (export via browser) and your local CA `cert.pem` (or any number of self-signed certs) into one `heimdall.pem` file.
- Place the `heimdall.pem` into the container (if you use Docker), for example by placing it in the path that you mapped to `/config`. Make sure that the Heimdall user has read access (`chmod a+r`).
- Set the `openssl.cafile` setting in `/config/php/php-local.ini` to your cert bundle:

```
# /config/php/php-local.ini
openssl.cafile = /config/heimdall.pem
```

Restart the container and the Enhanced apps should now be able to access your local HTTP websites. This configuration will survive updating or recreating the Heimdall container.

For remote icon downloads, Heimdall also verifies HTTPS certificates by default. If you cannot provide a proper CA bundle and need compatibility with an invalid/self-signed certificate, you can set:

```env
ALLOW_INSECURE_REMOTE_ICON_TLS=true
```

Use that only as a last resort. It disables certificate verification for remote icon downloads and reduces transport security.

## Allow Internal IP Requests

By default, Heimdall blocks requests to private or reserved IP addresses to mitigate potential security risks such as Server-Side Request Forgery (SSRF). However, you can enable access to internal IPs by setting the `ALLOW_INTERNAL_REQUESTS` environment variable in your `.env` file.

### Steps to Enable Internal IP Requests
1. Open your `.env` file located in the root directory of your Heimdall installation.
2. Add the following line:
   ```env
   ALLOW_INTERNAL_REQUESTS=true
   ```
   Setting this to `true` allows Heimdall to make requests to internal IP addresses (e.g., `192.168.x.x`, `10.x.x.x`, `127.0.0.1`).

3. Save the file and clear the Laravel configuration cache:
   ```bash
   php artisan config:clear
   ```

4. Restart your web server or development server:
   ```bash
   php artisan serve
   ```

### Default Behavior
If the `ALLOW_INTERNAL_REQUESTS` variable is not set or is set to `false`, Heimdall will block requests to private or reserved IP addresses and return a `403 Forbidden` error.

### Important Notes
- Enabling internal IP requests may expose your application to SSRF risks if your Heimdall instance is accessible from the internet. Ensure your instance is properly secured and not publicly accessible.
- Use this feature only if you trust the internal network and understand the security implications.

## Running offline
The apps list is hosted on github, you have a couple of options if you want to run without a connection to the outside world:
1) Clone the repository and host it yourself, look at the .github actions file to see how to generate the apps list.
2) Download the apps list and store it as a JSON accessible to Heimdall named `list.json`

With both options all you need to do is add the following to your `.env`
`APP_SOURCE=http://localhost/` Where `http://localhost/` is the path to the apps list without the name of the file, so if your file is stored at `https://heimdall.local/list.json` you would put `APP_SOURCE=https://heimdall.local/`

## Support
https://discord.gg/CCjHKn4 or through GitHub issues

## Donate
If you would like to show your appreciation, feel free to use the link below.

[![PayPal](https://heimdall.site/img/paypaldonate.svg)](https://www.paypal.me/heimdall)

## Credits
- PHP Framework - [Laravel](https://laravel.com/)
- Icons - [FontAwesome 5](https://fontawesome.com/)
- JavaScript - [jQuery](https://jquery.com/)
- Colour picker - [Huebee](http://huebee.buzz/)
- Background image - [pexels](https://www.pexels.com)
- Trianglify library - [Trianglify](https://github.com/qrohlf/trianglify)
- Everyone at Linuxserver.io that has helped with the app and let's not forget IronicBadger for the following question that started it all:
```
You know, I would love something like this landing page for all my servers' apps
that gives me the ability to pin favourites
and / or search
@Stark @Kode do either of you think you'd be able to rustle something like this up?
```

## License

This app is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
