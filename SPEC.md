# zoid6 — Specification

> **Last updated:** 2026-08-03.
> **Status:** Python router is v1 (production). PHP framework is
> legacy/stable — frozen at the `zoidweb4` line, no new features.

## Table of Contents

1. [Purpose & Scope](#1-purpose--scope)
2. [Status](#2-status)
3. [Python router](#3-python-router)
4. [Configuration schema](#4-configuration-schema)
5. [Service registration contract](#5-service-registration-contract)
6. [Per-site PHP framework](#6-per-site-php-framework)
7. [FHS install layout](#7-fhs-install-layout)
8. [Cross-reference map](#8-cross-reference-map)
9. [Out of scope / known limitations](#9-out-of-scope--known-limitations)
10. [Authoritative file index](#10-authoritative-file-index)

---

## 1. Purpose & Scope

`zoid6` is the unified BBS router and shared PHP framework for the
`bbsengine.org` / `zoidtechnologies.com` stack. It does two things:

1. **Python router (`src/zoid6/`)** — a single `MessageRouter` class
   that loads every enabled BBS module's router into one WebSocket
   server. The router is what `bed` runs when invoked as `zoid6`.
2. **PHP web framework (`php/`, `smarty/`, `shared/skin/`, `sites/`)** —
   the legacy library that powers `www.zoidtechnologies.com` and
   `engine.bbsengine.org`. Frozen at the `zoidweb4` line.

It does **not** own authentication (bed does), the database schema
(bbsengine6 does), the message wire protocol (bbsengine6 does), or
any individual game logic (each game ships its own `MessageRouter`).

### Relationship to sibling projects

```
bbsengine6  ── engine library (Python + PHP), TUI primitives, DB, net
   ▲
bed         ── WebSocket daemon (config loader, lifecycle, signals)
   ▲
zoid6       ── unified router (loads every enabled module)
   ▲
casino /    ── individual BBS games (each ships a MessageRouter)
empyre / …
```

`zoid6.main.main` is essentially `bed.main.main --router
zoid6.api.handler.MessageRouter --config /etc/zoid6/zoid6.json` with
an env-var override (`$ZOID6_CONFIG`) and a packaged default
fallback. The same resolver pattern lives in
[`bed/src/bed/_configpath.py:resolve_config_path`](../bed/src/bed/_configpath.py)
(added in commit `0ba05b2` for bed, mirroring the zoid6 design).

## 2. Status

| Area              | Status      | Notes                                               |
|-------------------|-------------|-----------------------------------------------------|
| `MessageRouter`   | v1 stable   | loads every enabled module; fails loudly on misconfig|
| `MonikerAuthRouter` | v1 stable | worked example; auth delegated to bed               |
| bundled `zoid6.json`| v1 stable   | 87 lines; 5 enabled modules + 4 disabled placeholders|
| FHS factory tree  | v1 stable   | byte-identical to packaged default; pinned by test  |
| `zoid6.tmpfiles`  | v1 stable   | systemd-tmpfiles drop-in                            |
| `php/zoid6.php`   | legacy/frozen | 1,723-line monolith; no new features planned    |
| `shared/skin/`    | legacy/stable | 30+ SCSS partials, 15+ tmpls; new sites reuse     |

## 3. Python router

### 3.1 Entry point

`src/zoid6/main.py:main` resolves a config path (env > FHS > bundled),
prepends `--config <path> --router zoid6.api.handler.MessageRouter`
to `sys.argv`, and forwards to `bed.main.main`. Every `bed` flag is
honoured because nothing else is stripped from argv.

```bash
$ zoid6 --port 9000 --foreground --debug
# resolves to:
# bed --config /etc/zoid6/zoid6.json
#     --router zoid6.api.handler.MessageRouter
#     --port 9000 --foreground --debug
```

### 3.2 `MessageRouter`

`src/zoid6/api/handler.py:MessageRouter`:

- `__init__(args, *, session_registry=None, secret=None,
  token_store=None, instance_id=None, clock=None)` — stores
  `args` and the auth wiring kwargs (forwarded by
  `bed.main.BED.start` when auth is enabled; see
  `bed/docs/BED_AUTH.md` "Adopting AuthService in a custom
  router"), loads config via `bed.config.load_config(args.config_file)`,
  validates the shape (raises on non-dict / non-dict `services`).
- `register_all(server)` — guards that bed's `AuthService` is
  registered first (it is, when bed loads any non-default router),
  then walks `config["services"]` and dispatches each enabled
  module's `MessageRouter` class.
- `_register_module(name, cfg, server)` — loads the module via
  `bbsengine6.module.load(args, cfg["modulepath"])`, looks up the
  `MessageRouter` class, instantiates it with the auth-wiring
  kwargs (so sub-routers like `casino.api.handler.MessageRouter`
  see the same `SessionRegistry` and crypto state the outer
  router received from bed — without this, sub-routers fall back
  to a private session manager and the first gameplay op after
  `auth` returns `not_authenticated`), and calls
  `register_all(server)`. Failures (ImportError, missing
  MessageRouter, generic exception, `TypeError` from a legacy
  sub-router that doesn't accept the kwargs) log via
  `io.echo(level=...)` and are non-fatal — a single broken module
  does not stop the daemon. The `TypeError` fallback retries
  with `RouterClass(args)` so routers that pre-date the kwargs
  contract still load.

After all modules have been registered, the router asserts that
`server.get_service("member_profile")` is not `None` — the BBS
universe is useless without the member service.

### 3.3 `MonikerAuthRouter`

`src/zoid6/api/monikerrouter.py:MonikerAuthRouter` is a worked
example. It:

- Accepts an injectable `provider=` for auth (defaults to
  `MonikerOnlyCredentialProvider`).
- Registers only `["ping"]` with the server (bed's `AuthService`
  already owns `auth`).
- Keeps `_handle_auth` as a documented private helper for games that
  want to subclass and override `register_all` to register a custom
  `auth` handler.

### 3.4 Config helpers

`MessageRouter.get_config()` reads the bundled `zoid6/data/zoid6.json`.
`MessageRouter.get_bundled_config_path()` returns its filesystem
path. `MessageRouter.get_enabled_services()` returns the list of
service names where `enabled == True`.

## 4. Configuration schema

The shipped `src/zoid6/data/zoid6.json` (also byte-identical to
`usr/share/factory/etc/zoid6/zoid6.json`) is 87 lines and has this
shape:

```json
{
  "bed": {"autorestart": true, "restart_delay": 5, "max_restarts": 10},
  "debug": false,
  "services": {
    "<module>": {
      "enabled": true | false,
      "modulepath": "dotted.python.path",
      "description": "free text",
      /* module-specific knobs go here */
    }
  },
  "bind":  {"host": "localhost", "port": 8765},
  "database": {"name": "zoid6", "host": "localhost", "port": 5432}
}
```

The `services` block is the router's input. Per-module knobs are
free-form but `casino` uses an opinionated sub-schema
(`table_service.*`, `game_service.*`, `chat_service.*`,
`bet_service`, `games`). The bundled config enables `member`,
`bank`, `channel`, `casino`. `auth` is documented but
disabled — bed handles auth natively. `empyre` and `murdermotel`
are documented placeholders pending implementation.

`bind` and `database` are bed-level knobs that flow through
unchanged.

## 5. Service registration contract

Every BBS module that wants to ride on zoid6 must:

1. Define `MessageRouter` (a class with `__init__(args, **kwargs)`
   and `register_all(server)`) at the modulepath listed in its
   `services` entry.
2. Accept a positional `args` (the parsed argparse Namespace from
   bed) and store it.
3. Accept the keyword-only auth-wiring kwargs
   `session_registry`, `secret`, `token_store`, `instance_id`,
   `clock` (any subset, all default to `None`). Bed forwards them
   via `MessageRouterClass(args, **router_kwargs)` when auth is
   enabled; the outer zoid6 router forwards the same kwargs to
   every sub-router it instantiates (see 3.2). A router that
   rejects these kwargs with `TypeError` is still loaded by the
   zoid6 router via the no-kwargs fallback — but it will then
   see a `None` session registry and `_check_access` will reject
   every gameplay op as `not_authenticated` even with a valid
   token. New modules should accept the kwargs. The full
   contract (kwargs forwarding at the bed/zoid6/casino triangle)
   lives in `bed/docs/BED_AUTH.md` "Adopting AuthService in a
   custom router" and `casino/docs/AUTH.md` §6.
4. In `register_all(server)`, register each message handler via
   `server.register_service(self, ["type_a", "type_b", …])`.
5. Implement `async handle_message(server, websocket, path, message)`
   on each registered service object. Returning a dict sends it back
   to that client only; returning `None` (or raising) lets the server
   decide what to do.
6. Not re-register `auth` — bed's `AuthService` owns it. If a game
   needs a custom auth flow, subclass `MonikerAuthRouter` and
   override `register_all` to register the custom handler alongside
   `ping`.

`bbsengine6.member.api.handler` is the canonical reference
implementation.

## 6. Per-site PHP framework

### 6.1 Shared library

Every site under `sites/` consumes `php/zoid6.php` (the monolith)
plus one or more of `libagora.php`, `librepo.php`, `libvulcan.php`.
`zoid6config.php` maps Smarty constants; `bootstrap.php` sets the
PHP `include_path`.

### 6.2 `smarty/` plugins

| Plugin                | Purpose                                            |
|-----------------------|----------------------------------------------------|
| `function.fa.php`     | Font Awesome icon helper                           |
| `modifier.ago.php`    | "x minutes ago" relative time                      |
| `modifier.datestamp.php` | Format a date stamp (full Python strftime vocabulary + `%O` achilles-specific per-user timezone opt-in; see `bbsengine6/handbook/specs/datestamp.md`) |
| `modifier.filesize.php` | Pretty-print file sizes                          |
| `modifier.fromnow.php` | "in x minutes" forward-relative time              |
| `modifier.linkurl.php` | Shorten / rewrite URLs                            |
| `modifier.summarize.php` | Truncate with ellipsis                          |

### 6.3 `shared/skin/scss/`

Canonical color/font variables in `_zoid6vars.scss` (forest-green
palette `#3a5a40` / `#dad7cd`, Roboto Condensed, metal-texture
background). About 30 partials build the framework stylesheet:
`page`, `pageheader`, `pagefooter`, `topbar`, `sidebar`,
`breadcrumbs`, `blurb`, `notify`, `form`, `form-checkbox`,
`lists`, `clock`, `tooltip`, `pagerinfo`, `pagelinks`, `poweredby`,
`redirectpage`, `errormessage`, `actions`, `accordion`, `animate`,
`asimov`, `breadcrumbs`, `maturecontentwarning`, `spoiler`,
`widelatin`, `about`, plus mixins/fonts/est2002.

### 6.4 `shared/skin/tmpl/`

`page.tmpl` is the cross-site HTML shell (smoothState, jQuery 3.7.1,
DOMPurify, bbsengine6.js, font-awesome). Plus `sidebar.tmpl`,
`blurb.tmpl`, `post.tmpl`, `sophia-post.tmpl`, `notify.tmpl`,
`maturecontentwarning.tmpl`, `noscript.tmpl`, `permissiondenied.tmpl`,
`googleanalytics.tmpl`, `latestreleases.tmpl`,
`mantra-detail.tmpl`, `southcarolinavoterinfoembed.tmpl`,
`vulcan-terse.tmpl`.

### 6.5 Sites

| Site                            | Purpose                                       |
|---------------------------------|-----------------------------------------------|
| `sites/www/`                    | zoidtechnologies.com production site          |
| `sites/engine/`                 | engine.bbsengine.org production site          |
| `sites/projects/`               | zoid6 project gallery                         |
| `sites/greetings/finnbday2024/` | Static greeting site (Finn's bday 2024)        |
| `sites/common/`                 | Shared per-site assets                        |
| `sites/static/`                 | (deprecated)                                  |
| `sites/teos/`                   | teos sub-site                                 |
| `sites/vulcan/`                 | vulcan sub-site                               |

Each site ships its own `config.php`, `htaccess-prod`, and Makefile.

## 7. FHS install layout

```
/etc/zoid6/zoid6.json           (installed from usr/share/factory/etc/zoid6/zoid6.json)
/var/lib/zoid6/                (created by zoid6.tmpfiles)
/var/log/zoid6/                (created by zoid6.tmpfiles)
/var/lib/bed/venv/bin/zoid6    (console script, installed by `bed make install-venv`)
```

`usr/share/factory/etc/zoid6/zoid6.json` is byte-identical to
`src/zoid6/data/zoid6.json`; the test suite pins this.

`usr/share/factory/etc/zoid6/zoid6.tmpfiles` is the systemd-tmpfiles
drop-in that creates `/var/log/zoid6` and `/var/lib/zoid6`.

The systemd unit lives in the `bed` repo (`bed/src/bed/daemon/bed.service`)
because the user, venv, and lifecycle all belong to bed.

## 8. Cross-reference map

| Concept                              | File                                       |
|--------------------------------------|--------------------------------------------|
| Router entry                         | `src/zoid6/main.py`                        |
| Unified router                       | `src/zoid6/api/handler.py`                 |
| Example router (MonikerAuthRouter)   | `src/zoid6/api/monikerrouter.py`           |
| Bundled config                       | `src/zoid6/data/zoid6.json`                  |
| FHS factory config                   | `usr/share/factory/etc/zoid6/zoid6.json`     |
| tmpfiles drop-in                     | `src/zoid6/daemon/zoid6.tmpfiles`          |
| Console-script manifest              | `src/pyproject.toml`                       |
| Daemon integration                   | [`bed`](../bed/)                           |
| Engine integration                   | [`bbsengine6`](../bbsengine6/)             |
| Casino (enabled module)              | [`casino`](../casino/)                     |
| PHP framework monolith               | `php/zoid6.php`                            |
| Cross-site stylesheet                | `shared/skin/scss/zoid6.scss`              |
| Cross-site HTML shell                | `shared/skin/tmpl/page.tmpl`               |

## 9. Out of scope / known limitations

- **Authentication** — bed's `AuthService` owns it. The bundled
  config marks `auth` as `enabled: false` deliberately.
- **Database schema** — bbsengine6 ships the `engine.*` schema and
  all migrations; this repo ships none.
- **Message wire protocol** — bbsengine6 defines it; games implement
  handlers against it.
- **Individual game logic** — each game (`casino`, future
  `empyre`/`murdermotel`) ships its own `MessageRouter`. zoid6 only
  loads and registers.
- **The PHP framework is frozen.** No new features planned. Sites
  that need new functionality should consume `bbsengine6.php` and the
  Smarty plugin set directly rather than growing `zoid6.php`.
- **`SKIN.md` is mislabeled.** Despite the filename, it documents
  casino `io.input*()` prompt color templates, not zoid6 skin
  conventions. It belongs in the `casino` repo.

### Database lifecycle

`zoid6` does not own database creation. The router reads
`zoid6.json:database.name` (default `zoid6`) and assumes the
named database is reachable.

**Self-heal (2026-08-20):** `bbsengine6.startup.main` now runs
a pre-flight that bootstraps the target database on first
invocation. When `python -m zoid6.main` (or any caller of
`bed.main.ensure_startup`) runs against a host where the
target DB has never been created, `bbsengine6.startup.main`
opens an admin pool against the `postgres` maintenance DB,
dispatches `stage_zero` (which calls `checkcreatedb` →
`checkdatabase` from `bbsengine6/py/src/bbsengine6/backend/`,
creating the DB if missing), and then dispatches `stage_one`
against a fresh target-DB pool. The operator therefore does
NOT have to run `python -m bbsengine6.startup` first; the
two-step order is still recommended in deployment scripts
for clarity, but is no longer required.

Manual bootstrap is still the recommended path on production
hosts:

```
python -m bbsengine6.startup --databasename zoid6
python -m zoid6.main
```

The first command opens a pool against the `postgres`
maintenance DB and runs `checkcreatedb` → `checkdatabase`
in stage_zero, which `CREATE DATABASE`s the target if
missing and then loads the `engine.*` schema. After
`bbsengine6.startup` returns True, the `zoid6` daemon can
be started and will talk to the now-present DB.

A `zoid6.startup` module is intentionally **not** planned.
Adding one would duplicate the privilege probe
(`checkcreatedb`) and the create-if-missing logic
(`checkdatabase`) outside the package that owns the
`engine.*` schema, and would create two owners for "is the
BBS DB ready?" — easy to drift. If a future change wants
to fold DB bootstrap into the router's startup sequence,
do it by extending `bbsengine6.startup` and calling it
from `zoid6.main` before `bed.main`, not by adding a new
`zoid6.startup` package.

## 10. Authoritative file index

| Path                                            | Role                                      |
|-------------------------------------------------|-------------------------------------------|
| `src/zoid6/main.py`                             | Console-script entry                      |
| `src/zoid6/api/handler.py`                      | MessageRouter                             |
| `src/zoid6/api/monikerrouter.py`                | Worked example                            |
| `src/zoid6/lib.py`                              | `PACKAGENAME = "zoid6"`                   |
| `src/zoid6/data/zoid6.json`                       | Bundled default config                    |
| `src/zoid6/daemon/zoid6.tmpfiles`               | systemd-tmpfiles drop-in                  |
| `src/zoid6/tests/conftest.py`                   | pytest fixtures                           |
| `src/zoid6/tests/test_bed_startup.py`           | FHS factory byte-equality                 |
| `src/zoid6/tests/test_config.py`                | Config loading                            |
| `src/zoid6/tests/test_message_router.py`        | MessageRouter end-to-end                  |
| `src/zoid6/tests/test_monikerrouter.py`         | MonikerAuthRouter                         |
| `src/zoid6/tests/test_validate_config.py`       | Config shape validation                   |
| `src/pyproject.toml`                            | Python manifest                           |
| `usr/share/factory/etc/zoid6/zoid6.json`          | FHS factory config (identical to bundled) |
| `usr/share/factory/etc/zoid6/zoid6.tmpfiles`    | FHS factory tmpfiles                      |
| `php/zoid6.php`                                 | PHP framework monolith                    |
| `php/zoid6config.php`                           | Smarty constant aliases                   |
| `php/bootstrap.php`                             | include-path setup                        |
| `smarty/function.fa.php`                        | Font Awesome helper                       |
| `smarty/modifier.ago.php`                       | Relative time ago                         |
| `smarty/modifier.linkurl.php`                   | URL rewriter                              |
| `shared/skin/scss/zoid6.scss`                   | Framework stylesheet                      |
| `shared/skin/scss/_zoid6vars.scss`              | Canonical color vars                      |
| `shared/skin/tmpl/page.tmpl`                    | Cross-site HTML shell                     |
| `sites/www/README.md`                           | Per-site README (the detailed one)        |
