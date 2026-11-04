# zoid6 TODO: install: build deploy workflow

Owner: cross-project (deploytool, bed, zoid6)
Open: 29   Done: 2

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---


The `deploytool` repo (`deploytool/Makefile`) ships the canonical,
minimal pattern for TUI-package deploys:

```
PROJECT = deploytool
OUTDIR = dist/
VERSION := $(shell date +%Y%m%d%H%M)
WHEEL := $(OUTDIR)$(PROJECT)-0.0.1.dev$(VERSION)-py3-none-any.whl

version:
    @echo '__version__ = "0.0.1.dev$(VERSION)"' > src/$(PROJECT)/_version.py
    @echo '__datestamp__ = "$(VERSION)"' >> src/$(PROJECT)/_version.py
    @echo '__githash__ = "'`git log -1 --format='%H' ... | cut -c 1-16`'"' >> src/$(PROJECT)/_version.py

build: version
    cd src && $(PYTHON) -m build --outdir ../$(OUTDIR)

install: build
    $(PIP) install --no-deps $(WHEEL)

deploy-tui: install
```

The whole workflow collapses to: **`build` produces a wheel in
`dist/`, `install` runs `pip install --no-deps` against that
just-built wheel, `deploy-tui` is an alias for `install`.** No
editable installs, no `setup.py build && sudo setup.py install`,
no multi-wheel dance — just "build the whl, then install the whl
you just built."

The sibling packages all do this differently today and should be
migrated to the same pattern, primary goal being "install the whl
file that was just built":

- [ ] **empyre** — `empyre/Makefile` still uses the legacy
      `setup.py` flow (`./setup.py build && sudo ./setup.py
      install`). The `install` target should be rewritten to
      `version → build (python3 -m build --outdir dist/) →
      install (pip install --no-deps dist/empyre-*.whl)` with
      `deploy-tui: install`. Drop the `setup.py` references
      once the wheel install path works. (`empyre/setup.py-setuptools`
      is the relic file; `src/` is already pyproject-style.)
- [ ] **bed** — already has the right shape (`install-venv`
      and `deploy-venv` both run `python3 -m build --wheel` and
      `pip install` the resulting wheel), but the venv dirs
      (`/var/lib/bed/venv`, `/tmp/bed-$$`) and the
      multi-wheel dependency dance (bbsengine6 + bed) are
      bed-specific. (getdate_next is no longer in this set —
      pip resolves it as a transitive runtime dep of
      bbsengine6 via `bbsengine6/py/pyproject.toml`.) Trim the
      top-level `bed/Makefile` so the primary `install: build`
      target matches deploytool (install the just-built
      `bed-*.whl` into the active venv with
      `pip install --no-deps`), and keep the
      `install-venv` / `deploy-venv` targets as the prod /
      shared-venv wrappers. Add `deploy-tui: install` alias.
- [ ] **bbsengine6** — `bbsengine6/Makefile` `deploy-tui` is
      `$(MAKE) -C py/src install` and `py/src/Makefile` `install`
      is `pip install --no-cache-dir -e .` (editable mode).
      Replace the editable `pip install -e .` with the
      deploytool pattern: `install: build` runs
      `pip install --no-deps` against the wheel just produced
      by `py/`'s `build` (writes to `py/dist/` or
      `/srv/repo/bbsengine6/`). Add `deploy-tui: install` so the
      top-level `deploy-tui` target is a one-liner.
- [ ] **zoid6** — `zoid6/src/Makefile` already has
      `version`, `build`, and `install`; `install` is currently
      `pip install --no-cache-dir -e .` (editable). Replace
      with deploytool's `install: build` + `pip install --no-deps
      $(WHEEL)` so the just-built wheel from `OUTDIR` is what
      gets installed. Keep `install-venv` / `install-fhs` as the
      prod wrappers. Confirm top-level `zoid6/Makefile`
      `deploy-tui: $(MAKE) -C src deploy-tui` still works after
      the swap.
- [ ] **paymentflow** — 3 console scripts in
      `paymentflow/src/pyproject.toml`; same migration.
- [ ] **achilles**, **asimov**, **backuptools**, **moneyday**,
      **murdermotel**, **socrates**, **vulcan/tui**,
      **yummyjam/article2**, **zoidoffice** — each has 1 console
      script per the shebang-alignment section above; their
      Makefiles (where present) should adopt the same
      `install: build` + `deploy-tui: install` shape once they
      are migrated off `[project.scripts]` to `bin/` launchers.

Migration checklist (per package, mirrors deploytool):

- [ ] `PROJECT`, `OUTDIR`, `VERSION := $(shell date +%Y%m%d%H%M)`,
      `WHEEL := $(OUTDIR)$(PROJECT)-0.0.1.dev$(VERSION)-py3-none-any.whl`
      variables at the top of the Makefile.
- [ ] `version` target stamps `src/<pkg>/_version.py` with
      `__version__`, `__datestamp__`, `__githash__` (cut to
      16 chars, matching deploytool).
- [ ] `build: version` runs `cd src && python3 -m build
      --outdir ../$(OUTDIR)` (or the package-equivalent — e.g.
      `bbsengine6` builds from `py/`).
- [ ] `install: build` runs `$(PIP) install --no-deps $(WHEEL)`
      — the whole point: **install the whl that was just built**.
- [ ] `deploy-tui: install` (one-line alias) so the top-level
      `make deploy-tui` works the same way across all repos.
- [ ] `pyproject.toml` at `src/pyproject.toml` (or `py/...`) with
      `[tool.setuptools.dynamic] version = { attr = "<pkg>._version.__version__" }`
      so the stamped `_version.py` is what gets baked into the
      wheel metadata.
- [ ] Verify: `make deploy-tui` runs end-to-end and
      `$(VIRTUAL_ENV)/bin/python -c "import <pkg>; print(<pkg>.__version__)"`
      prints the stamped version (proves the whl install took).

Open question: should `install` always write to `dist/` (deploytool
default, fast iteration loop) or to `/srv/repo/<pkg>/` (the bed /
bbsengine6 / zoid6 convention, shared with `rename-sdist` +
`sign` + `release`)? Recommendation: **keep `OUTDIR` configurable
per package** — `dist/` for the dev-loop `install` target, but
the same `build` target can be re-pointed at `/srv/repo/<pkg>/`
for `make release`. Both paths use the same `WHEEL := $(OUTDIR)...`
expression so the `install` target just works against either.



Decision: **all packages drop their wheels into
`/srv/repo/<pkg>/`, not `dist/`.** The bed / bbsengine6 / zoid6
Makefiles already use `/srv/repo/$(PROJECT)/`; deploytool and
empyre use `dist/` and need to be brought in line. A single
canonical drop point per package means:

- the deploy host always knows where to find the latest wheel
  (`ls -t /srv/repo/<pkg>/*.whl | head -1`),
- `rename-sdist`, `sign`, and `release` operate on the same
  artifacts the dev-loop `install` target just consumed,
- a shared `repo` group with `2775` perms on `/srv/repo/<pkg>/`
  (already wired in `bed/Makefile` `ensure-build-dir` and
  `bbsengine6/Makefile` `ensure-build-dir`) lets multiple
  developers build into the same directory without sudo,
- one convention for `deploytool` to scrape when assembling the
  shared venv on the deploy host.

Migration (per package):

- [ ] **deploytool** — change `OUTDIR = dist/` to
      `OUTDIR = /srv/repo/$(PROJECT)/` in `deploytool/Makefile`;
      drop the existing `deploytool/dist/*.whl` and
      `deploytool/dist/*.tar.gz` artifacts (they're stale
      `dist/` builds). Add `ensure-build-dir` (or reuse the
      `bed` / `bbsengine6` pattern: chgrp `repo`, chmod `2775`)
      so devs without sudo on `/srv/repo` can still build.
      Verify: `make deploy-tui` writes
      `/srv/repo/deploytool/deploytool-0.0.1.dev<ts>-py3-none-any.whl`
      and `pip install --no-deps` against that path works.
- [ ] **empyre** — `empyre/Makefile` `deploy` and `build`
      targets currently hardcode `python3 -m build --outdir dist`;
      introduce `OUTDIR = /srv/repo/$(PROJECT)/` (matching the
      bed / bbsengine6 / zoid6 convention) and rewrite both
      targets to use it. Add the same `ensure-build-dir`
      helper. Verify: `make build` drops
      `/srv/repo/empyre/empyre-0.0.1.dev<ts>-py3-none-any.whl`.
- [ ] **bed** — already uses `OUTDIR = /srv/repo/$(PROJECT)/`;
      no change needed, but confirm `deploy-venv` writes there
      too (currently uses `/tmp/$(PROJECT)-$$` and copies the
      wheel into the venv). Optional: switch `deploy-venv` to
      build straight into `/srv/repo/bed/` and `pip install`
      from there, eliminating the `/tmp` intermediate.
- [ ] **bbsengine6** — already uses `OUTDIR = /srv/repo/$(PROJECT)/`;
      no change needed. Confirm `py/src/Makefile`'s
      `version` target writes to `py/src/bbsengine6/_version.py`
      (it does; the top-level `build` target runs from `py/`).
- [ ] **zoid6** — already uses `OUTDIR = /srv/repo/$(PROJECT)/`
      in both the top-level `zoid6/Makefile` and
      `zoid6/src/Makefile`; no change needed.
- [ ] **paymentflow**, **achilles**, **asimov**, **backuptools**,
      **moneyday**, **murdermotel**, **socrates**, **vulcan/tui**,
      **yummyjam/article2**, **zoidoffice** — when each is
      migrated to the deploytool `install: build` pattern per
      the section above, set `OUTDIR = /srv/repo/$(PROJECT)/`
      (not `dist/`) from the start.



Every project whose Makefile invokes `python -m build` needs a
`PREPARE_BUILD` macro in front of that invocation, mirroring
`bed/Makefile:163-185`: rename a foreign-owned `build/` (or
`py/build/`, etc.) to `build.stale.$$` and `chmod 1775` the
replacement. Without it, a `build/` left behind by another user
(e.g. `jam`) causes setuptools to EPERM on the `chown` it does
when populating `dist-info/`. Mode `1775` (sticky + group write,
**no setgid**) is intentional: sticky prevents file stomping,
group write lets other `opencode`-group members rebuild, and the
absence of setgid stops setuptools' `shutil.copystat` from
propagating setgid onto `dist-info/` (which EPERMs in the
SELinux + NoNewPrivs container).

Status, per project:

- [x] **bed** — macro at `bed/Makefile:163-185`, called from
      `build` / `install-venv` / `deploy-venv` and from each
      dep build (`$(BBSENGINE_DIR)`, `$(CURDIR)`).
      (`$(GETDATE_DIR)` was removed — getdate_next is now resolved
      as a transitive pip dep of bbsengine6.)
- [x] **getdate_next** — has a stub at
      `getdate_next/Makefile:32` (`mkdir -p build && chmod g-s
      build`) that does NOT handle the foreign-owned case. If
      `build/` is owned by `jam`, this stub will EPERM and
      `python -m build` will fail. Upgrade to match the bed
      pattern. (No `TODO.md` in this project; tracked here.)
      **Done 2026-08-20**: replaced with the bed-pattern
      `PREPARE_BUILD` (foreign-owned rename + `chmod 1775`).
      `deploytool/tests/test_deploy_getdate_next_tui.py` pins
      both invariants.
- [ ] **bbsengine6** — see `bbsengine6/TODO.md` "PREPARE_BUILD
      protection for in-tree `py/build/`". Current `build/` is
      `jam:opencode 2775` (foreign-owned AND setgid, both bugs).
- [ ] **empyre** — see `empyre/TODO.md` "Build infrastructure".
      `deploy` target runs `python -m build` from project root.
- [ ] **asimov** — see `asimov/TODO.md` "Build infrastructure".
      Separate pre-existing bug: no `pyproject.toml` at root or
      under `src/asimov/`, so `python -m build` is currently
      broken regardless. Fix layout first, then PREPARE_BUILD.
- [ ] **vulcan/tui** — `vulcan/tui/Makefile:8` invokes
      `python -m build` against `vulcan/tui/` (which has a
      `pyproject.toml`, so the build target is live). Needs
      PREPARE_BUILD. (No `TODO.md` in this project; tracked
      here.)

Zoid6 itself is exempt from the `PREPARE_BUILD` macro:
`zoid6/src/Makefile:285-291` `install-user` uses `pip wheel`
rather than `python -m build` for the per-package wheel build,
and `zoid6/src/Makefile:132-133` `build` (the primary wheel
producer consumed by `deploy-tui`) declares `clean` as a
prerequisite, so a stale `build/` or `zoid6.egg-info/` is
wiped before every `python -m build` invocation. The clean
dep sidesteps the setuptools SOURCES.txt absolute-path failure
mode that surfaces when `zoid6.egg-info/SOURCES.txt` carries
forward absolute paths from a prior run.

The setuptools 80+ `install_egg_info` → `dist-info` EPERM
issue (kernel rejecting SGID preservation in the SELinux +
NoNewPrivs container) is **separate** from the SOURCES.txt
fix and may still surface on hosts with stricter SGID
enforcement. If it does, drop `python -m build` down to
`pip wheel --no-deps --no-build-isolation --no-clean` (the
shape already used by `install-user`) for the affected
target only.

Shared setup (one-time, on the deploy host):

- [ ] Confirm `/srv/repo` exists, group-owned by `repo`, mode
      `2775`. If not: `sudo chgrp repo /srv/repo && sudo chmod
      2775 /srv/repo` (matches `bed/Makefile` `ensure-repo`).
- [ ] For each migrated package, `sudo install -d -m 2775 -o
      <dev-user> -g repo /srv/repo/<pkg>/` (or let the
      package's `ensure-build-dir` target do it).
- [ ] Add the `opencode` user to the `repo` group so
      `make build` works without sudo from any developer shell.

Verification (after the migration, on the deploy host):

- [ ] `ls -t /srv/repo/deploytool/*.whl | head -1` returns the
      most recently built deploytool wheel.
- [ ] `ls -t /srv/repo/empyre/*.whl /srv/repo/bed/*.whl
      /srv/repo/bbsengine6/*.whl /srv/repo/zoid6/*.whl` all
      return recent wheels (no `dist/` leakage).
- [ ] `make deploy-tui` from each repo drops a wheel into
      `/srv/repo/<pkg>/` and installs it into the active venv
      in one shot — the canonical dev-loop workflow.
