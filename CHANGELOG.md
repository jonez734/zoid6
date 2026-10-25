# Changelog

All notable changes to `zoid6` are recorded here.

The format is loosely based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### fix(config): the bank module is disabled so bed's BankService is the only bank handler

zoid6's `bank` entry registered `bank_*` a second time with bbsengine6's
direct-table `BankServiceHandler`, which shadowed bed's definer-function handler
and failed with "permission denied to set role". `bank` is `enabled: false` in
`zoid6.json` and in its factory copy. Existing installs must set the same in
`/etc/zoid6/zoid6.json`.

### chore(deps): pin bed and bbsengine6 to 0.0.3

`bed>=0.0.3.dev202610` and `bbsengine6>=0.0.3.dev202610`, so an older bed or
bbsengine6 cannot satisfy zoid6, which depends on the 0.0.3 login and bank fixes.

### chore(version): 0.0.3.dev series

`make version` stamps `0.0.3.dev<datestamp>`. The stamp had been `0.0.1.dev`,
which left a stale package looking current to pip.

### feat(config): zoid6 selects db token persistence, so tokens survive restarts

`auth.token_persistence` is `db` in `zoid6.json` and its factory copy. bed stores only the
SHA-256 digest of each token, in `engine.__bed_token`, so a client can reconnect after a
daemon restart.

### feat(config): `session.idle_minutes` is 15, matching `bed.json` in both config files

### fix(config): the owner-migration superuser is taken from `PGUSER`, not a constant

### fix(router): accept and store bed's `channel_state` in `MessageRouter`

### feat(smarty): `|datestamp` modifier gets the full strftime vocabulary and `%O`

`zoid6/smarty/modifier.datestamp.php` is a thin wrapper around the bbsengine6 body. The
modifier accepts the full strftime vocabulary, plus `%O` as an opt-in ordinal form.

### feat(template): `\zoid6\template` namespace and `\zoid6\plugin_extra()`

`\zoid6\template\extra()` contributes the shared template directory. `refresh_smarty_aliases()`
re-runs the `SMARTY*` aliasing, so a vhost that defines the namespaced constants after
`zoid6config.php` loads can call it.

### refactor(zoid6constants): `FONTAWESOMEURL` and `GOOGLEANALYTICSACCOUNT` move to `zoid6constants.php`

They are loaded transitively through `zoid6config.php`, and each is guarded so a vhost can
override it. `ACHILLESURL` is dropped.

### fix(sites/www): SMARTY defines come before the `zoid6config.php` require

### feat(shared/page.tmpl): the FA Kit script tag is enabled

### fix(zoid6config): `zoid6config.php` loads `bbsengine6config.php` transitively

### fix(zoid6config): honour a `TEOSURL` environment override

### fix(zoid6): the `shared/skin/art` descent is gated on the directory existing

### config(zoid6): `websocket.ping_interval` 60s and `ping_timeout` 600s

### fix(zoid6): the factory `zoid6.json` is refreshed from source

### refactor(make): `stage` / `prod` deploy shape

`stage` builds the shared chrome, smarty, and markdown trees locally.
`prod` runs `stage`, pushes the zoid6 tree and the shared chrome to
merlin, and ensures the php and markdown push. `deploy` is now an alias
to `prod`. `markdown` no longer runs an `rsync --dry-run`; its merlin push
is `parsedown-deploy`, which `prod` runs.

Removed: the duplicate `php` stage; the `art` step in `shared/skin`
(`shared/skin/art/` does not exist and the `-` prefix hid the failure).
`deploy-www`, `deploy-tui`, `deploy-prod`, and `shared` keep their names.

### refactor(smarty): zoid6 |datestamp modifier body extracted to bbsengine6/php/smarty_modifier_datestamp.php

`zoid6/smarty/modifier.datestamp.php` is now a 2-line stub
that requires the canonical modifier body from
`bbsengine6/php/smarty_modifier_datestamp.php` via a
`__DIR__`-relative `require_once`. The previous arrangement
(2026-10-15 commit `bb4cb03` making the file byte-identical
with the bbsengine6 sibling) was correct but required any
future change to be applied in lockstep. The new
arrangement eliminates the "must be byte-identical" invariant
by having both files require a single source of truth.

The zoid6 modifier body is now:

```php
\bbsengine6\util\smarty_plugin_bootstrap();
require_once("config.php");
require_once(__DIR__ . "/../../bbsengine6/php/smarty_modifier_datestamp.php");
```

The relative path requires bbsengine6 to be installed as a
sibling of zoid6 (the canonical install layout on production
and in dev checkouts). If a future deploy puts bbsengine6
elsewhere, this path must be updated to match.

The test (`php/test_datestamp_modifier.php`) also simplified:
instead of `eval`-ing the modifier file with a
`require_once("config.php")` stub, it now `require_once`s the
canonical body directly and calls `smarty_modifier_datestamp()`
as a regular function.

Behavior is unchanged. All 38 zoid6 modifier tests pass.

### refactor(smarty): zoid6 |datestamp modifier is now byte-identical with bbsengine6's

`zoid6/smarty/modifier.datestamp.php` and
`bbsengine6/smarty/modifier.datestamp.php` are now byte-for-byte
identical. The bbsengine6 file was previously the
VHOSTDOCROOT-bootstrap variant; the zoid6 file was the bare
wrapper. Both now share the same content (3-line thin wrapper
around `\bbsengine6\util\datestamp()` plus a single
`\bbsengine6\util\smarty_plugin_bootstrap()` call to prime
include_path for `bbsengine6config.php`).

`diff bbsengine6/smarty/modifier.datestamp.php
zoid6/smarty/modifier.datestamp.php` returns nothing.

The bootstrap is idempotent: in the bbsengine6 standalone case
it primes the include_path; in the zoid6-using vhost case (which
already loaded zoid6config.php → bbsengine6config.php before the
plugin is loaded) it's a no-op. Either way, both modifiers emit
byte-identical output because they delegate to the same
helper.

### feat(php): byte-exact parity with Python's strftime for `\bbsengine6\util\datestamp()`

The canonical walker in `bbsengine6/php/util.php` now pins
`LC_TIME=C` at entry so day/month names match Python's
C-locale strftime output, and space-pads the `%l` token to
match Python's `" 2"` (vs PHP's native `"2"`). The Python
walker now explicitly handles `%:z` (which Python's strftime
does not support natively). With these changes, the PHP
modifier and Python's `bbsengine6.util.datestamp` produce
**byte-exact** output for any common-subset token on a server
with `LC_TIME=C`. The contract is enforced by
`bbsengine6/test_parity_php_python.json` (a shared JSON
fixture read by both `test_parity_php_python.php` and
`test_parity_php_python.py`).

### feat(smarty): extend |datestamp modifier with full Python strftime vocabulary + %O per-user TZ opt-in

The `|datestamp` Smarty modifier was previously a 3-line shim that
called `date(\config\DATEFORMAT, $string)` with no strftime
translation. The format string was passed straight to `date()`,
which produced broken output for any strftime format containing
tokens other than PHP `date()`'s letters.

Replaced the shim with a token-aware walker that matches
bbsengine6's modifier:

- Supports the full Python strftime vocabulary: `%Y %y %m %b %B %d
  %j %H %I %l %M %S %p %P %Z %z %:z %a %A %%`.
- Adds `%O` opt-in token for per-user timezone offset (reads
  `$_SESSION['localtimezoneoffset']`, renders `±HH:MM`).
- Signature: `smarty_modifier_datestamp($timestamp, $format = null)`.
  Format arg optional; bare modifier reads `\config\DATEFORMAT`
  (falls back to bare `DATEFORMAT`, then `%Y-%m-%d %H:%M`).
- Bare letters in the format string pass through literally (strftime
  convention); multi-character tokens like `%:z` are matched before
  their single-letter prefixes.

Test coverage: `zoid6/php/test_datestamp_modifier.php` — 38 tests
covering every common-subset token, the default-format fallback,
the `%O` opt-in, edge cases, and the token-aware walker.

Spec: `bbsengine6/handbook/specs/datestamp.md` (the authoritative
reference, written in bbsengine6 because it documents both the
Python and PHP surfaces).

Sibling change in bbsengine6: the engine's modifier (which had
been using a 11-entry strftime→date translation table) was
rewritten with the same walker and now supports the same
vocabulary.

### docs(zoid6): cross-reference the 2026-10-03 bbsengine6 env() REDIRECT_<key> fix

The teos vhost (zoidtechnologies.com) was rendering
`NEEDINFO:TEOSLABEL.buildbreadcrumbs.100` as the "You are here"
breadcrumb root label on every `<vhost>/<uri>/` page, even
though `teos/www/htaccess-prod` declares `SetEnv TEOSLABEL teos`
and the engine reads it via `\bbsengine6\util\env('TEOSLABEL', ...)`.

Root cause (full breakdown in inner bbsengine6
`CHANGELOG.md` `[Unreleased]` and `bbsengine6/ROBUSTNESS_REVIEW.md`
Phase 9 / Finding 9.1): mod_rewrite's internal redirect from
`/<uri>` to `/engine/router.php?uri=<uri>` re-evaluates
per-directory config for `/engine/`. The teos htaccess is **not**
in scope at `/engine/` (the vhost has no `html/engine/.htaccess`),
so the `SetEnv` value survives only as `REDIRECT_TEOSLABEL`.
The handbook vhost never showed the bug because its docroot
htaccess (`bbsengine6/www/org/htaccess-prod`) sits at the docroot
(`RewriteBase /`) and governs `/engine/` too.

`\zoid6\util\env()` was not changed in this commit, but the
sibling `\config\TEOSURL` and `\config\TEOSDIR` constants set by
`zoid6/php/zoid6config.php` are now the **tier-2 fallback** behind
the new `\bbsengine6\util\getenv_redirect()` walk inside
`\bbsengine6\util\env()`. The constants continue to do the work
they always did (CLI tests, partial deploys without vhost htaccess,
etc.) and now sit one tier behind a REDIRECT_<key>-aware reader.
`bbsengine6\util\vhost_label()` was updated to route through
`env('TEOSLABEL')` so `bbsengine6\blurb\getlabel()` and
`engine/router.php::router_buildBreadcrumbs` see the same var.
Zoid6's own `zoid6/php/zoid6config.php:80-95` and the
`\config\*` namespace constants it sets are unchanged.

No code changes in this repo. This entry exists to cross-link
the bbsengine6 fix and to remind operators that the zoid6
constants remain a tier-2 fallback behind env() (not the primary
read path). The regression guard for this contract is
`bbsengine6/php/test_env.php` (+11 REDIRECT_ cases) and
`bbsengine6/php/test_router.php` Test 6e + new Test 6e.1; the
end-to-end harness lives at `bbsengine6/teos/www/test_htaccess_setenv_reaches_php.sh`.

### feat(template): \zoid6\template namespace + \zoid6\plugin_extra()

Companion change to the bbsengine6 template/plugin registry
refactor. Two new public symbols:

```
\zoid6\template
├── PRIORITY_APP, PRIORITY_APP_INTEGRATION, PRIORITY_ENGINE
├── _registry() (private)
├── register(priority, hook)
├── extra()                  → ["/srv/www/zoid6/shared/skin/tmpl/"]
└── normalize(raw)           [wrapper → \bbsengine6\template\normalize]

\zoid6\plugin_extra()        → ["/srv/www/zoid6/smarty/"]
```

`\zoid6\template` mirrors `\bbsengine6\template`'s shape so
the two are intentionally symmetric. The namespace exposes the
priority-enum registry and the canonical `extra()` contribution;
`normalize()` is a thin wrapper that delegates to the
canonical in `\bbsengine6\template` with this namespace's
registry and engine-fallback path.

bbsengine6config.php auto-registers both at
`PRIORITY_APP_INTEGRATION` when zoid6config.php loads (i.e.
when the vhost's config-prod.php does
`require_once('zoid6config.php')` before engine.php loads
bbsengine6config.php). The same opt-in pattern as the
existing `\bbsengine6\menu\buildchoices` shim: no hard
dependency on zoid6.

**Loading order.** `\bbsengine6\template\normalize()` is
called by `\zoid6\template\normalize()` via the
canonical-form delegation. bbsengine6 must be loaded before
any caller invokes `\zoid6\template\normalize()`. The
existing load order (vhost config-prod.php requires
zoid6config.php which transitively loads bbsengine6config.php
via engine.php) satisfies this.

**File restructured.** The pre-existing zoid6config.php
global-namespace content is now wrapped in an explicit
`namespace { ... }` block. A new `namespace zoid6 { ... }`
block holds `\zoid6\plugin_extra()`. A new `namespace
zoid6\template { ... }` block holds the registry and
resolver. The `?>` closer remains at the bottom of the
file.

No public-facing behavior changes for vhosts that already
load zoid6config.php: their `SMARTYTEMPLATESDIR` and
`SMARTYPLUGINSDIR` resolution flows through the new
registry automatically. Vhosts that don't load zoid6 are
unaffected.

### feat(bbsengine6): password column hardening — legacy MD5-crypt audit + bcrypt CHECK constraint

Resolves the three checkboxes in `zoid6/TODO.md` "Password column
hardening — legacy MD5-crypt migration" via `bbsengine6` (the schema
authority) and one operator-facing audit helper that surfaces the
2026-08-22 incident symptom on every auth attempt.

**Why:** the 2026-08-22 `bed auth login` incident took four `psql`
probes to diagnose because `checkpassword` silently returned `False`
when the stored password was a `$1$` MD5-crypt hash instead of the
expected bcrypt. Three follow-ups close the gap:

1. **One-shot audit** — `bbsengine6.member.audit_password_column(args)`
   returns the list of monikers holding a legacy `$1$` hash. Test pin:
   `bbsengine6/py/tests/test_member_legacy_hash_audit.py` (6 unit cases
   + 1 live-DB regression pin asserting `len(rows) == 0`). Operator
   workflow: run the helper against the live `zoid6` DB, migrate each
   result via `bbsengine6/scripts/setpassword.py <moniker> <newpassword>`
   (legacy plaintext is lost; members must re-set their password).

2. **Writer audit + bcrypt CHECK constraint** — `bbsengine6/sql/member.sql`
   now documents the bcrypt-only invariant in a top-of-file comment.
   `bbsengine6/sql/manage_password_format.sql` (new; `\i`-included from
   `bbsengine6/sql/bbsengine6.sql` after `member.sql`) adds
   `chk_member_password_bcrypt` on `engine.__member.password`:
   `password ~ '^\$2[abxy]\$'`. Any future INSERT/UPDATE with a non-
   bcrypt prefix (including `$1$`) fails at write time. The constraint
   is `DROP CONSTRAINT IF EXISTS` then `ADD CONSTRAINT` so re-running
   `bbsengine6.sql` against an existing DB is a no-op.

3. **Per-auth audit** — `bbsengine6.member.audit_password_hash(args,
   moniker, **kwargs)` reads `engine.member.password` for the moniker
   and returns a `PasswordHashAudit` namedtuple
   (`present, non_empty, prefix, is_bcrypt, is_md5crypt, length_ok`).
   Wired into `member.checkpassword` immediately before the bcrypt
   round-trip SELECT, so every auth attempt (bed `auth login` / `auth
   reconnect`, casino auth prompt, future entry points) emits one
   diagnostic line:
   - bcrypt OK → `level="ok"` (green-bg prefix per
     `bbsengine6/io/echo.py:1317`)
   - `$1$` legacy → `level="warning"` with hash prefix
   - NULL / empty / non-bcrypt prefix / wrong length →
     `level="warning"` with the specific reason
   
   On `is_md5crypt=True` the audit logs the warning AND the round-trip
   proceeds (legacy plaintext might still match the MD5 hash; the
   warning is the operator signal, not a hard reject).

Test pin: `bbsengine6/py/tests/test_member_audit_password_hash.py`
(16 unit cases — 6 parametrized per the TODO matrix + 4 specific cases
+ 6 wire-up tests covering `checkpassword`'s audit invocation). All
22 unit tests pass; existing tests (test_console_member_add_edit.py,
test_auth_password_e2e.py, test_password_hash_scrypt.py,
test_member_verify_found.py) unchanged.

The 2026-08-22 incident scenario is now reproduced as
`TestCheckpasswordCallsAudit::test_checkpassword_invokes_audit_on_md5_hash_and_continues`:
stored `$1$` hash, plaintext doesn't match, `checkpassword` returns
`False` AND emits the `W: jam password is MD5-crypt ($1$AUNKK0aN$...),
migrate to bcrypt` diagnostic the operator would have seen on the
first failed login.

Cross-ref: `bbsengine6/TODO.md` (back-reference added) and
`zoid6/TODO.md` "Password column hardening" (all three checkboxes
now ticked, with the audit-and-migrate workflow split between the two
repos per the TODO note).

### feat(zoid6): forward `module_config` to sub-routers + channel admin handler

`MessageRouter._register_module` (in `zoid6.api.handler`) now
forwards `config=module_config` to sub-router constructors.
Routers that don't accept the kwarg fall back to the bare
`RouterClass(self.args)` call via the existing `TypeError`
fallback — older routers are unaffected.

The `services.channel` entry in `zoid6.json` gained an
`admin_handler` sub-key with `enabled: true` and the verb list
(`channel_create`, `channel_list`, `channel_get`,
`channel_set_announce_only`, `channel_add_announcer`,
`channel_remove_announcer`). With this opt-in, deployments using
zoid6.json expose the WS-side admin handler alongside the
subscription handler. Operators are still expected to use
`con channel-*` instead of the WS verbs.

### feat(bbsengine6.startup): install chk_member_password_bcrypt + audit at every bootstrap

The 2026-08-22 hardening (above) only lands the constraint if an
operator manually runs `psql \\i bbsengine6.sql` against the target
DB — `bbsengine6.startup` itself does not load the umbrella
`bbsengine6.sql` file (its stage loops load individual SQL files
via `database.importsql()` driven by the checkclass / checkfunction
lists, neither of which references `manage_password_format.sql`).

This follow-up wires the install + audit into `bbsengine6.startup`
itself so the constraint lands automatically on every bootstrap:

* New `bbsengine6.backend.checkpasswordformat` module — installs
  `chk_member_password_bcrypt` via `database.importsql()` inside a
  SAVEPOINT (idempotent: `manage_password_format.sql` itself uses
  `DROP CONSTRAINT IF EXISTS` + `ADD CONSTRAINT`), then calls
  `bbsengine6.member.audit_password_column` on the same connection.
  Wired into `backend/stage_one.py`'s module tuple immediately
  after `checkclasses`, so the engine schema and `engine.__member`
  table are already in place when the constraint lands.
* New `bbsengine6.database.constraintexists` helper — schema-filtered
  lookup against `pg_constraint` + `pg_namespace`, mirroring the
  shape of `classexists` / `functionexists` / `typeexists` /
  `schemaexists`.
* New `bbsengine6.backend.lib.checkpasswordformat` entry point.
* New tests: `tests/test_checkpasswordformat.py` (11 cases pinning
  install + audit) + `tests/test_database_helpers.py` (5 cases
  pinning `constraintexists`). All 16 pass.

For operators: no manual `psql \\i bbsengine6.sql` re-run required.
The audit log line on a clean column reads
`audit_password_column: 0 row(s) with $1$ hash in engine.__member`
(level="ok", green-bg prefix). On a DB with stale legacy hashes the
audit emits a `level="warning"` line listing the affected monikers —
the same signal as the live-DB regression pin in
`test_member_legacy_hash_audit.py`.

Cross-repo close-out: 7 casino test fixtures (which were writing
raw `gen_salt('md5')` INSERTs and stomping bcrypt from
`setpassword.py`) have been migrated to use
`bbsengine6.member.setpassword`. The orphan
`casino/src/casino/sql/test_data.sql` has been deleted. See
`casino/CHANGELOG.md` [Unreleased] and `casino/TODO.md` "Test
fixture migration: `gen_salt('md5')` → `gen_salt('bf')`".

### build: add `PREPARE_BUILD` macro to `src/Makefile`

`zoid6/src/Makefile` lacked the `PREPARE_BUILD` helper that
`bed/Makefile:189-194`, `getdate_next/Makefile:32-36`, and
`casino/Makefile:75-110` (this release) already have. Without it,
the freshly-mkdir'd `build/` inherits the parent directory's
setgid bit (mode `2775`), and setuptools' `shutil.copystat`
mirrors that mode onto the new dist-info directory during
`bdist_wheel` — which then EPERMs the subsequent chmod in
SELinux-enforcing + NoNewPrivs containers (we lack `CAP_FSETID`).

The macro is identical to `bed/Makefile:189-194`, parameterized
over `$(1)`, and called from the `build` target with
`$(CURDIR)`.

The TODO list previously marked `src/Makefile` exempt from
PREPARE_BUILD because `install` is `pip install -e .` (no
`python -m build`). But the `build` target still runs `python
-m build`, so the EPERM cascade applies to it. The macro is added
here as defense-in-depth so a future `make build` (or `make
deploy-tui` which chains into `build`) can't regress on the
EPERM.

Tracked in `zoid6/TODO.md` "PREPARE_BUILD standardization
(cross-project)" — that checkbox is now ticked.

### fix(zoid6): build depends on clean to wipe stale SOURCES.txt absolute paths

`src/Makefile:132` `build` now declares `clean` as a prerequisite
(in addition to `version`). The `clean` target removes `build/`,
`zoid6.egg-info/`, and the various `__pycache__` /
`.pytest_cache` / `.ruff_cache` / `.mypy_cache` directories
before each `python -m build` invocation.

**Why:** setuptools' `egg_info` re-reads the existing
`zoid6.egg-info/SOURCES.txt` on every build and merges its
entries into the new manifest. If the existing SOURCES.txt
contains absolute paths (e.g. from a prior run with a stale
or hand-edited manifest), setuptools rejects the new manifest
with:

```
error: Error: setup script specifies an absolute path:
    /home/opencode/data/work/zoid6/src/zoid6/__init__.py

setup() arguments must *always* be /-separated paths relative to the
setup.py directory, *never* absolute paths.
```

Wiping `zoid6.egg-info/` (and `build/`) before each `make build`
makes the absolute-path failure mode unreachable — the manifest
is regenerated from scratch with relative paths every time.

`make build` (and downstream `make deploy-tui`) now runs the
full `clean` cycle on every invocation; the cost is one
incremental rebuild per deploy rather than reusing any cached
artifacts. Verified: `deploy zoid6.tui` completes all green
from a freshly-cleaned `src/` tree.

### deploy-tui: install from `/srv/repo/zoid6/` wheel by default; `DEPLOY_EDITABLE=1` for editable

Part of the cross-monorepo Phase 1 work in `deploytool`'s
`--editable` flag (see `deploytool/CHANGELOG.md` `[Unreleased]`).
zoid6's `deploy-tui` target now matches the pattern shared by
`bbsengine6`, `bed`, `casino`, and `deploytool`.

Before: `zoid6/src/Makefile deploy-tui: install` called
top-level `install`, which was `$(PIP) install --no-cache-dir -e $(SRC)` —
**always** editable, never went through `/srv/repo/zoid6/`.

After:

- Default: `deploy-tui: build` then `pip install $WHEEL`,
  where `$WHEEL` is the most-recently-built wheel under
  `/srv/repo/zoid6/zoid6-*.whl` picked via `ls -t | head -1`.
- `DEPLOY_EDITABLE=1` (set by `deploytool --editable`):
  `pip install --no-cache-dir -e .` from the project root,
  with `rm -rf *.egg-info` to wipe stale absolute paths in
  `SOURCES.txt`.

**Behavior change**: `zoid6.tui` deployments run without
`--editable` now install from a wheel rather than editable
from source. The previous shape was always editable; the new
default is "install from wheel." `zoid6.prod` (which delegates
to `install-fhs` → `/var/lib/zoid6/venv`) is unaffected by
`DEPLOY_EDITABLE` — that's still the sudo-umbrella flow.

Verified: `make -n -C zoid6/src deploy-tui` shows
`pip install /srv/repo/zoid6/zoid6-*.whl`; the same with
`DEPLOY_EDITABLE=1` shows `pip install -e .`.

### zoid6: config resolver is the canonical pattern (env > FHS > bundled)

`zoid6/src/zoid6/main.py:_resolve_config_path()` is the
intentional, supported config-resolution chain for the `zoid6`
console script:

1. `$ZOID6_CONFIG` env var (override; e.g. testing, sandbox)
2. `/etc/zoid6/zoid6.json` (FHS-installed; production path)
3. the packaged `src/zoid6/data/zoid6.json` shipped in the wheel
   (dev-box path; no `sudo install-etc` required)

The resolver landed in `7ffb7c0` ("zoid6: run from venv via
pyproject console script", 2026-08-03) and is already documented
in `README.md` (Quick start) and `SPEC.md` (§1, §3.1). This
CHANGELOG entry records the design intent so future contributors
don't "fix" the no-config case by reintroducing a `--config`
required-arg path. See `TODO.md` "## Config resolver (env > FHS
> bundled)" for the cross-monorepo rationale and the bed-side
counterpart (`bed/src/bed/_configpath.py:resolve_config_path`,
added in commit `0ba05b2`).

### zoid6: `deploy-prod` Makefile target + `zoid6.prod` deploytool sub-target

- `zoid6/src/Makefile` adds `deploy-prod: install-fhs` (alias
  for the existing sudo umbrella). Added to `.PHONY` and the
  `help` block.
- `zoid6/Makefile` (top-level) adds a `deploy-prod` shim
  delegating to `src/Makefile:deploy-prod`, mirroring the
  existing `install-fhs` shim.
- `deploytool/src/deploytool/lib.py` `TARGETS["zoid6"]` is now
  `["www", "tui", "prod"]` — `prod` is opt-in (auto-dropped
  for `deploy zoid6` per `lib.py:296`, same rule as
  `bed.prod`).
- `zoid6.tui` is unchanged: `deploy-tui: install` →
  `pip install -e .` against the active venv (non-sudo).
  `zoid6.prod` is the sudo umbrella:
  `deploy-prod: install-fhs` → sysusers + tmpfiles + venv +
  systemd + /etc/zoid6.
- See `zoid6/TODO.md` "## `zoid6.tui` (non-sudo) /
  `zoid6.prod` (sudo) deploy split" for the rationale.

### zoid6: out-of-band reassign script targets zoid6 role

- `src/zoid6/reassign_bootstrap_owners.py` `TARGET` is now
  `"zoid6"` (was `"opencode"`).
- The script now creates the `zoid6` role if missing
  (`NOSUPERUSER NOCREATEDB NOCREATEROLE NOLOGIN INHERIT`) before
  iterating over the SECURITY DEFINER helpers, so a fresh dev DB
  can be migrated to the new ownership model without first running
  `bbsengine6`'s full `python -m bbsengine6.startup` sequence.
- `src/Makefile` `reassign-bootstrap-owners` echo updated to
  reflect the new target role.

This change aligns the out-of-band migration script with the
canonical owner of the five `public.*` SECURITY DEFINER helpers
(`manage_schema_priv`, `manage_database_priv`, `manage_role_privs`,
`manage_secondary_role`, `get_role_privs`) that
`bbsengine6.database.verify_function_owner` now requires. See
`bbsengine6/TODO_zoid6_role.md` for the follow-up that drops
`"postgres"` from the allow-list next release.

### zoid6: rename config file from `bed.json` to `zoid6.json`

- `src/zoid6/data/bed.json` → `src/zoid6/data/zoid6.json`
- `usr/share/factory/etc/zoid6/bed.json` → `usr/share/factory/etc/zoid6/zoid6.json`
- FHS install path: `/etc/zoid6/bed.json` → `/etc/zoid6/zoid6.json`
- `get_config()` / `get_bundled_config_path()` now load
  `zoid6.json`; `main.py` resolves `/etc/zoid6/zoid6.json`.
- `src/Makefile` `install-etc` / `uninstall-etc` /
  `factory-sync` updated to the new filename.
- `tests/test_config.py` and `tests/test_bed_startup.py`
  updated; the FHS byte-equality test now compares
  `usr/share/factory/etc/zoid6/zoid6.json` to
  `src/zoid6/data/zoid6.json`.
- Note: this is a breaking change for hosts that already have
  `/etc/zoid6/bed.json` installed — `make install-etc` will
  install the new file alongside or instead of the old one,
  and existing hosts should `sudo mv /etc/zoid6/bed.json
  /etc/zoid6/zoid6.json` (or just rerun `make install-etc`
  to overwrite) before the next `zoid6` start.

### zoid6: run from venv via pyproject console script

- `src/pyproject.toml` now registers `zoid6 = "zoid6.main:main"` so a
  `pip install -e .` from `src/` (or via `bed make install-venv`)
  produces a working `zoid6` console script at
  `/var/lib/bed/venv/bin/zoid6` (or wherever the venv lives).
- `start-bed.sh` was removed in favor of the console script.

### zoid6: Phase 5 packaging consolidation

- `src/Makefile` `version` target now writes `__githash__` and
  `__datestamp__` (matching `bed`, `bbsengine5`, `bbsengine6`)
  instead of the unprefixed `githash` / `datestamp` fields.
- `src/zoid6/_version.py` regenerated by the new target.

### zoid6: Phase 4 refactor MonikerAuthRouter onto bed's provider

- `MonikerAuthRouter` delegates auth to bed's provider via an
  injectable `provider=` constructor arg.
- `register_all` registers only `["ping"]` (bed's AuthService owns
  `auth`). `_handle_auth` is kept as a documented private helper
  that preserves the historical wire-level distinctions.
- `handler.py`: `print(...)` → `io.echo(..., level=...)` for
  module-load failures. New `_validate_config` rejects non-dict
  configs at startup so misconfiguration fails loudly.

### zoid6: handler delegates _load_config to bed.config.load_config

- `MessageRouter._load_config()` now delegates to
  `bed.config.load_config` (env-var merging, override support,
  no fallback search). `args.config_file` is required.
- `get_config()` / `get_bundled_config_path()` helpers retained for
  external callers (e.g. the `start-bed.sh` shim's last vestiges).

### zoid6: FHS plumbing for /etc/zoid6/bed.json + rename start-bed.sh

- `usr/share/factory/etc/zoid6/bed.json` is byte-identical to
  `src/zoid6/data/bed.json`; a test in `tests/test_bed_startup.py`
  pins this.
- `usr/share/factory/etc/zoid6/zoid6.tmpfiles` is the systemd
  drop-in that creates `/var/log/zoid6` and `/var/lib/zoid6`.
- The legacy `start-bed.sh` shim is renamed (now `bin/zoid6` via the
  pyproject console script — see latest entry).

### zoid6: log bed.json path, pass --config explicitly in start-bed.sh

The shim logs the resolved `bed.json` path before exec so misconfig
failures are diagnosable from the systemd journal.

### zoid6: article2 entry in topbar navigation choices

The topbar across every site under `sites/` picks up an
"article2" link so visitors can reach
[`article2`](../yummyjam/article2/) without a separate bookmark.

### zoid6: backuptools entry in topbar navigation choices

The topbar across every site under `sites/` picks up a
"Backuptools" link so operators can reach
[`backuptools`](../backuptools/) without a separate bookmark.

### zoid6: FHS integration with bed.json

The factory tree at `usr/share/factory/etc/zoid6/` lands. The
systemd unit that runs `zoid6` lives in the `bed` repo (it needs
the `bed` user / venv); this repo only ships the tmpfiles drop-in
and the factory config.

### zoid6: unify per-site smarty / scss / js under shared/skin/

The cross-site assets (SCSS partials, Smarty templates, JS helpers)
are consolidated under `shared/skin/`. Per-site skins now
`@import` from `shared/skin/scss` rather than carrying their own
copy of every partial.

## [0.0.1.dev202608032051] — 2026-08-03

### Initial extracted release

- First cut as a self-contained package extracted from the meta-repo
  where `zoid6/` lived as a plain directory before being
  repo-ized.
- Python: `MessageRouter` + `MonikerAuthRouter` +
  `zoid6` console-script entry, full `bed.json` schema, FHS factory
  tree.
- PHP: 1,723-line `php/zoid6.php` monolith, four sibling libs,
  Smarty plugins, vendored Parsedown, cross-site stylesheet, 15+
  cross-site Smarty templates.
- Sites: `www`, `engine`, `projects`, `greetings/finnbday2024`,
  `common`, `static` (deprecated), `teos`, `vulcan`.
- Handbook: `SMOOTHSTATE.md`.
- Tests: `tests/test_bed_startup.py` (FHS byte-equality),
  `tests/test_config.py`, `tests/test_message_router.py`,
  `tests/test_monikerrouter.py`, `tests/test_validate_config.py`.

---

For unimplemented features and future work, see
[`TODO.md`](TODO.md).
