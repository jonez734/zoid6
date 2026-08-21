# Changelog

All notable changes to `zoid6` are recorded here.

The format is loosely based on [Keep a Changelog](https://keepachangelog.com/),
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

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
