# zoid6

> **Unified BBS module router and PHP web framework for the zoidtechnologies.com
> / bbsengine.org stack.**

`zoid6` is a Python package that ships a single `MessageRouter` which
loads every BBS module (`member`, `bank`, `channel`, `casino`, …) into
one WebSocket server, plus the legacy PHP framework that powers the
`www.zoidtechnologies.com` and `engine.bbsengine.org` websites. It is
the glue layer between [`bbsengine6`](../bbsengine6/) (the engine),
[`bed`](../bed/) (the daemon), and the BBS games (currently
[`casino`](../casino/), with `empyre` and `murdermotel` on the
roadmap).

> **See [`SPEC.md`](SPEC.md)** for the full entry-point specification:
> the router, the configuration schema, the per-site PHP framework, and
> the per-game module contract.
>
> This README is a quick-start. `SPEC.md` is the spec. `CHANGELOG.md`
> is the history.

## Quick start

```bash
pip install -e .
zoid6                       # uses /etc/zoid6/zoid6.json if present,
                            # else the packaged zoid6/data/zoid6.json
```

The `zoid6` console script is a thin wrapper around `bed --router
zoid6.api.handler.MessageRouter`. It resolves a config path in this
order:

1. `$ZOID6_CONFIG` (env-var override)
2. `/etc/zoid6/zoid6.json` (FHS-installed config)
3. the packaged `src/zoid6/data/zoid6.json`

and then forwards to `bed.main.main`. Every flag `bed` accepts
(`--host`, `--port`, `--debug`, `--foreground`, `--pidfile`, …) is
honoured because `zoid6` only prepends `--config` and `--router` to
`argv`.

### Smoke test

```bash
pip install -e .
zoid6 --port 8765 --foreground

# in another terminal
wscat -c ws://127.0.0.1:8765
> {"type":"ping"}
< {"type":"pong"}
```

### Production

The `usr/share/factory/etc/zoid6/zoid6.json` factory default is installed
to `/etc/zoid6/zoid6.json` by the host-package installer. The systemd
unit lives in the `bed` repo (`bed/src/bed/daemon/bed.service`) and
expects to be invoked as:

```bash
/var/lib/bed/venv/bin/zoid6 --config /etc/zoid6/zoid6.json
```

`make install` (in `bed/`) will set up the `bed` user, venv, and
systemd unit; this repo only ships the FHS factory tree and the
`zoid6.tmpfiles` drop-in for `/var/log/zoid6`, `/var/lib/zoid6`.

## What's in this repo

`zoid6` is dual-language — a modern Python WebSocket-router package
plus a legacy PHP website framework that has been in production since
the `zoidweb4` era.

```
zoid6/
├── src/zoid6/                   Python package (the modern half)
│   ├── main.py                  `zoid6` console-script entry — forwards to bed
│   ├── api/
│   │   ├── handler.py           MessageRouter — loads all enabled modules
│   │   └── monikerrouter.py     Worked-example router (delegates auth to bed)
│   ├── data/
│   │   └── zoid6.json             Bundled default config
│   ├── daemon/
│   │   └── zoid6.tmpfiles       systemd-tmpfiles drop-in
│   └── tests/                   pytest (config + MessageRouter)
│
├── php/                         Legacy PHP framework
│   ├── zoid6.php                Monolithic lib (config + sidebar + captcha +
│   │                            access control + notify + post + breadcrumb)
│   ├── zoid6config.php          Smarty constant aliases + CAPTCHA env
│   ├── zoid6constants.php       ACHILLESURL constant
│   ├── bootstrap.php            include-path setup
│   ├── libagora.php             agora / amznitem domain
│   ├── librepo.php              project domain
│   ├── libvulcan.php            link domain
│   └── vulcan.php               vulcan namespace
│
├── smarty/                      Smarty plugins (fa, ago, datestamp,
│                                filesize, fromnow, linkurl, summarize)
├── markdown/                    Vendored Parsedown + MarkdownExtra
├── scss/                        Forest-green palette variables
│   └── _zoid6vars.scss          (canonical copy under shared/skin/scss/)
├── shared/skin/                 Cross-site SCSS, JS, and Smarty templates
│   ├── scss/                    ~30 partials (page, sidebar, topbar, blurb, …)
│   ├── tmpl/                    page.tmpl (the cross-site shell),
│   │                            sidebar, blurb, post, notify, … (~15)
│   └── js/                      smoothstate, accordian
│
├── sites/                       Per-vhost site trees (production deploys)
│   ├── www/                     zoidtechnologies.com production site
│   ├── engine/                  engine.bbsengine.org production site
│   ├── projects/                zoid6 project gallery
│   ├── greetings/finnbday2024/  Static greeting site
│   ├── common/                  Shared per-site assets
│   ├── static/                  (deprecated)
│   ├── teos/                    teos sub-site
│   └── vulcan/                  vulcan sub-site
│
├── handbook/                    User / developer documentation
│   └── SMOOTHSTATE.md           Page-transition framework guide
│
├── usr/share/factory/etc/zoid6/ FHS factory defaults (zoid6.json + tmpfiles)
│
├── index.php                    5-line stub (requires config.php + session.php)
├── Makefile                     Root build orchestrator
├── SKIN.md                      (mislabeled — actually casino io.input*()
│                                prompt color templates)
└── TODO.md                      Line-numbered open work
```

## The MessageRouter

`zoid6.api.handler.MessageRouter` is what `zoid6` loads into `bed`.
It reads the bundled or FHS-installed `zoid6.json`, walks the
`services` block, and imports each enabled module's `MessageRouter`
class. The result is one WebSocket server speaking every BBS module's
wire protocol in a single process.

`MessageRouter.register_all(server)` requires `bed`'s `AuthService`
to be registered first (bed does this natively before any non-default
router runs). The router also refuses to start without the `member`
service — every other module needs it.

The shipped config enables:

| Module      | Modulepath                            | Notes                                 |
|-------------|---------------------------------------|---------------------------------------|
| `auth`      | `auth.api.handler`                    | **disabled** — bed handles natively   |
| `member`    | `bbsengine6.member.api.handler`       | profile / tier / referrals            |
| `bank`      | `bbsengine6.bank.api.handler`         | balance / transfers / history         |
| `channel`   | `bbsengine6.channel.api.handler`      | pub/sub messaging                     |
| `casino`    | `casino.api.handler`                  | blackjack / poker / yahtzee           |
| `empyre`    | `empyre.api.handler`                  | (not yet implemented)                 |
| `murdermotel` | `murdermotel.api.handler`           | (not yet implemented)                 |

The `casino` block in `zoid6.json` is more detailed than the rest — it
configures the per-game actions (`hit`, `stand`, `double`, `split`,
`surrender`), the table lifecycle (`list_tables`, `create_table`,
`join_table`, `leave_table`, `watch_table`), and the chat surface
(`table_chat`, `global_chat`, `emote`).

## PHP framework

The `php/` directory is the legacy shared library used by every site
under `sites/`. The crown jewel is `zoid6.php` — a 1,723-line
monolith that bundles configuration, sidebar rendering, captcha,
access control, notify delivery, post helpers, breadcrumb rendering,
and the page-getter. Sites consume it via `zoid6config.php` (which
maps Smarty constants) and `bootstrap.php` (include-path setup).

`smarty/` ships the per-site plugin set; `shared/skin/scss/` is the
framework stylesheet (forest-green palette `#3a5a40` / `#dad7cd`,
Roboto Condensed, metal-texture background, flexbox layout); and
`shared/skin/tmpl/page.tmpl` is the cross-site HTML shell
(smoothState, jQuery 3.7.1, DOMPurify, bbsengine6.js, font-awesome).

Each `sites/<name>/` directory is a self-contained vhost with its own
`config.php`, `htaccess-prod`, `Makefile`, and (where applicable)
`skin/`, `php/`, and `js/` trees. `sites/www/` is the most developed
and has its own README.

## FHS install

```
/etc/zoid6/zoid6.json         (from usr/share/factory/etc/zoid6/zoid6.json)
/var/lib/zoid6/              (created by zoid6.tmpfiles)
/var/log/zoid6/              (created by zoid6.tmpfiles)
/var/lib/bed/venv/bin/zoid6  (console script, installed by bed)
```

The factory tree is byte-identical to the packaged default; a test in
`tests/` pins this. To install fresh:

```bash
cd /path/to/zoid6
sudo rsync -a usr/share/factory/etc/zoid6/ /etc/zoid6/
sudo install -m 0644 src/zoid6/daemon/zoid6.tmpfiles /usr/lib/tmpfiles.d/zoid6.conf
sudo systemd-tmpfiles --create
```

The systemd unit and the venv live in the `bed` repo.

## Dependencies

- Python 3.9–3.12
- [`bbsengine6`](../bbsengine6/) (engine + TUI primitives)
- [`bed`](../bed/) (WebSocket daemon, >= 0.0.1.dev2026)
- PHP 8.1 (legacy web layer)
- PostgreSQL 13+

## Tests

```bash
cd zoid6
PYTHONPATH=src:../bbsengine6/py/src:../bed/src pytest src/zoid6/tests
```

The suite covers config loading, the bundled-config path, the
MessageRouter, the MonikerAuthRouter example, and the FHS factory
byte-equality invariant.

## License

GPL-2.0-or-later.
