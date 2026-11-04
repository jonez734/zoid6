# zoid6 TODO: Console scripts and _version.py

Owner: cross-project
Open: 25   Done: 0

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---


The bed (`2ab078e`), casino (`0865e23`), and zoid6 (this commit)
repos have all been migrated to the same pattern: real launcher
files under `bin/` with `#!/usr/bin/env python3.12`, plus
`[tool.setuptools] script-files` (no matching `[project.scripts]`).
This is "Option A" per the shebang-alignment discussion. The
remaining sibling packages still ship shell-wrappers or stale
auto-generated `[project.scripts]` entries with hardcoded
`#!/usr/bin/env python3` (or no shebang at all) and should be
migrated to the same pattern, in dependency order (zoid6 → bed →
casino → the rest):

- [ ] **achilles** — 1 console script. Add `bin/achilles`,
      `[tool.setuptools] script-files`, drop `[project.scripts]`.
- [ ] **article2** — 1 console script (collides with
      `yummyjam/article2`'s `article2` script; resolve the
      collision first by renaming one — likely keep `article2`
      as the game-name script and rename the other to
      `article2-cms` or similar). Two `bin/` launchers, two
      `[tool.setuptools] script-files` entries.
- [ ] **asimov** — 1 console script.
- [ ] **backuptools** — 1 console script.
- [ ] **bbsengine6** — 1 console script (`bed`); collides with
      `bed/bin/bed`. Resolve the collision by either
      (a) deprecating `bbsengine6`'s `bed` script (it predates
      the real `bed` package and is now a duplicate) or
      (b) renaming it to `bbsengine6-tools` / `bbs-bed` etc.
      Recommend (a) since `bed` is the canonical entry point.
- [ ] **deploytool** — 1 console script.
- [ ] **empyre** — 1 console script.
- [ ] **moneyday** — 1 console script.
- [ ] **murdermotel** — 1 console script.
- [ ] **paymentflow** — 3 console scripts.
- [ ] **socrates** — 1 console script (and the
      `socrates._version` build-error fix from the
      `## socrates editable install fails` section below is a
      prerequisite before any packaging change here can be
      verified end-to-end).
- [ ] **vulcan/tui** — 1 console script.
- [ ] **yummyjam/article2** — 1 console script (the second half
      of the `article2` collision pair above).
- [ ] **zoidoffice** — 1 console script.

Notes for whoever picks this up:

- **Editable installs** lose the auto-generated launcher
  entirely per setuptools docs (only `[project.scripts]` fires
  in editable mode, and we're dropping those). For dev
  workflows, either (a) run `pip install .` (non-editable) from
  each repo, or (b) layer 1 the venv shebang like I did for
  my workflow — patch
  `/home/opencode/.venv/bin/<script>` to
  `#!/usr/bin/env python3.12` once after each `pip install -e`.
- **Two-pyproject collision**: some of these packages have a
  stale inner `pyproject.toml` (e.g. casino did at
  `src/pyproject.toml`). Delete the inner one before doing the
  migration; the outer is canonical. `grep -l
  "\[project.scripts\]" <pkg>/src/pyproject.toml` is the
  quick check.
- **Verification command** (per repo):
  `/home/opencode/.venv/bin/python3 -m pytest -q` should still
  pass (the migration doesn't touch runtime code). Then
  `cd <pkg>/bin && head -1 <script>` should print
  `#!/usr/bin/env python3.12`.



### Local state (zoid6)

`zoid6/src/zoid6/_version.py` is already in the target shape:

```python
__version__ = "0.0.1.dev202606261106"
githash = "e242b2e59327395f"
datestamp = "202606261106"
```

Only `__version__` is double-underscored — and that's the conventional public-API
name (re-exported from `zoid6/__init__.py:4` as `from zoid6._version import
__version__` and asserted in `zoid6/tests/test_message_router.py:280-284`).
The two stamp-time constants (`githash`, `datestamp`) are single-underscored.

**No local rename work is required in zoid6.** Treat zoid6 as the reference
scheme for the rest of the monorepo.

### What "remove the `__`" means here

The `__version__` symbol stays as-is — it is the public API. The work is
about the **non-version** stamp-time constants. Across the monorepo these
are currently inconsistent:

| Package | Stamp-time constants | Notes |
|---|---|---|
| `zoid6` | `githash`, `datestamp` | **target shape** |
| `bed` | `githash`, `datestamp` | matches zoid6 |
| `empyre` | `githash`, `datestamp` | matches zoid6 |
| `murdermotel` | `githash`, `datestamp` | matches zoid6 |
| `backuptools` | `githash`, `datestamp` | matches zoid6 |
| `bbsengine6` | `githash`, `__datestamp__` | **the outlier** — `__datestamp__` was kept during the 2026-06-26 cleanup to preserve the symbol that the new `bbsengine6.__init__.py` re-exports as `bbsengine6.__datestamp__` |

The divergence surfaced during the 2026-06-26 `_version.py` cleanup
(`bbsengine6` and the orphan `io/_version.py` were normalized that day).
The cleanup deliberately did **not** rename `bbsengine6._version.__datestamp__`
→ `datestamp` because doing so would silently break the public symbol
`bbsengine6.__datestamp__` that was exposed in the same change.

### Why this matters

- Inconsistency makes cross-package code (e.g. a test that imports both
  `bbsengine6` and `zoid6`) awkward: `import bbsengine6; bbsengine6.__datestamp__`
  works, but `import zoid6; zoid6.__datestamp__` raises `AttributeError`.
- The leading-underscore convention in Python signals "internal to the
  module" — `__datestamp__` (double-underscore) signals name-mangling
  territory, which is misleading for a module-level constant that is
  intended to be importable. `bbsengine6` should match the
  `githash`/`datestamp` shape used by the rest of the monorepo.
- The dunder prefix in `_version.py` is also fragile under the
  `setuptools` dynamic-attr mechanism (`attr = "pkg._version.__datestamp__"`),
  which has to special-case attribute access on module attributes.

### Tasks

- [ ] **Pick a single symbol name** for the stamp-time constant across
      the monorepo. Recommendation: `datestamp` (matches 5 of 6 packages
      today, including zoid6).
- [ ] **Migrate `bbsengine6`**:
  - `bbsengine6/py/src/bbsengine6/_version.py` — rename `__datestamp__`
    → `datestamp`.
  - `bbsengine6/py/src/Makefile:41` — change the `__datestamp__` line
    to `datestamp`.
  - `bbsengine6/py/src/bbsengine6/__init__.py:1` — change
    `from ._version import __version__, __datestamp__` to
    `from ._version import __version__, datestamp`.
  - `bbsengine6/py/tests/test_bed_shim.py` — no change (the shim tests
    don't reference `bbsengine6.__datestamp__`).
  - Re-run `make version` in `bbsengine6/py/src` after the rename so
    the next build's `_version.py` has the new symbol.
- [ ] **No action required in zoid6** (already correct). This entry
      exists so the cross-monorepo cleanup has a stable reference point
      and so zoid6's `__init__.py` re-export contract is documented.
- [ ] **Update `bed/TODO.md`, `empyre/TODO.md`, `murdermotel/TODO.md`,
      `backuptools/TODO.md` cross-references** to point at this entry
      (or at the equivalent in their own TODO files) once the
      `bbsengine6` migration lands.

### Cross-references

- `asimov/TODO.md` "_version.py files: clean up orphans and standardize
  stamping" — broader cross-monorepo cleanup that includes the
  `__datestamp__` vs `datestamp` decision as a sub-task.
- `bbsengine6/py/src/bbsengine6/_version.py` — current `__datestamp__`
  definition; the one place that needs to change.
- `bbsengine6/py/src/bbsengine6/__init__.py:1` — re-exports
  `__datestamp__` at the package level; needs to be updated in lockstep.
- `bbsengine6/py/src/Makefile:38-41` — `version` target writes the
  `__datestamp__` line; needs to be updated in lockstep.

---



### Problem

Running `pip install -e /home/opencode/data/work/socrates --no-deps`
fails with:

```
ModuleNotFoundError: socrates._version
  File ".../setuptools/config/expand.py", line 199, in _find_spec
    raise ModuleNotFoundError(module_name)
error: metadata-generation-failed
```

The full traceback is `setuptools` trying to resolve the dynamic
`version` attribute declared in `socrates/pyproject.toml:48-49`:

```toml
[tool.setuptools.dynamic]
version = { attr = "socrates._version.__version__" }
```

The module `socrates._version` is **not on disk**. It only exists
transiently after running `make version` in `socrates/` (see
`socrates/Makefile:17-18`):

```make
version:
	@echo '__version__ = "$(VERSION)"' > src/socrates/_version.py
```

So:

- A fresh clone of the monorepo followed by `pip install -e socrates`
  fails immediately.
- A `pip install -e .` inside `socrates/` only works if the developer
  has just run `make version` — and that target has a side effect of
  writing a file that the developer then has to remember to delete
  before committing (the `clean` target on `socrates/Makefile:20-22`
  does this: `rm -f src/socrates/_version.py`).
- The bug surfaced during the 2026-06-26 venv consolidation:
  `pip check` against `/home/opencode/.venv` reported `bed 1.0.0
  requires bbsengine6>=9.0.0, but you have bbsengine6
  0.0.1.dev202606261622 which is incompatible` and `socrates
  0.0.0.dev202606051123 has requirement bbsengine6>=9.0, but you have
  bbsengine6 0.0.1.dev202606261622 which is incompatible`. The
  `bed` warning was fixable by reinstalling `bed` editable (which
  refreshed its dist-info from the new `__init__.py` re-export). The
  `socrates` warning was **not** fixable that way: `pip install -e
  socrates --no-deps` to refresh its dist-info fails with the
  `ModuleNotFoundError` above, leaving the stale `>=9.0` constraint
  in place. **This is the open follow-up flagged in the
  `2026-06-26 venv consolidation` report.**

### Why this matters

- A `git clone && pip install -e socrates` workflow is broken for
  every developer, not just the agent.
- The `>=9.0` constraint in `socrates/pyproject.toml:29` is itself
  wrong (it was baked in before the 2026-06-26 `bbsengine6`
  `_version.py` cleanup; `bbsengine6`'s current version is
  `0.0.1.dev202606261622`). The build-error masks this because the
  editable install can't even reach the metadata-generation step.
- The socrates `Makefile:17-18` writes **only** `__version__` — no
  `githash`, no `datestamp`. This is inconsistent with the rest of
  the monorepo (see the `_version.py symbol names` section above
  for the cross-monorepo picture). Fixing the build error is a
  natural opportunity to also bring socrates's `make version` into
  line with the `bed`/`zoid6`/`bbsengine6`/`empyre` pattern.

### Fix options

#### Option A — minimal: check in a generated `_version.py`

Commit `src/socrates/_version.py` to git (with a dev version like
`0.0.0.dev0` and a `githash = ""` placeholder), and update the
`clean` target so it does **not** delete it. This is the pattern
used by `bed/_version.py`, `zoid6/_version.py`, `bbsengine6/_version.py`,
`empyre/_version.py` — all of those files are committed to git and
overwritten by `make version` at build time.

- **Pros:** Smallest change. `pip install -e socrates` works
  immediately. Follows the established monorepo pattern.
- **Cons:** Commits a generated file (slightly out of step with
  best practice, but consistent with the rest of the monorepo).

#### Option B — make `setuptools` not need `_version.py`

Switch socrates from a dynamic version attr to a static
`version = "0.0.0.dev0"` in `pyproject.toml`, dropping the
`[tool.setuptools.dynamic]` block. The `make version` target then
becomes advisory (it would still write `_version.py` for runtime
`from socrates._version import __version__` imports, but
`pip install -e .` would no longer require the file to exist).

- **Pros:** `pip install -e socrates` works without any `_version.py`
  file. No generated files committed to git.
- **Cons:** Diverges from the monorepo pattern (every other project
  uses the dynamic-attr approach). The wheel's published `Version:`
  field would always be `0.0.0.dev0` until the `make version` step
  was added to the build pipeline (currently `build: version` is the
  only consumer, so the wheel would always be `0.0.0.dev0`).

#### Option C — fix the `make version` target + commit a stub

Combine the two: commit a stub `src/socrates/_version.py` (matching
the `bed`/`zoid6` three-line shape: `__version__`, `githash`,
`datestamp`) so the build works out of the box, and also extend the
`make version` target to write the `githash` and `datestamp` lines
in the monorepo-standard pattern. This is what the
`2026-06-26 bbsengine6 cleanup` did — it's the path with the most
upside.

- **Pros:** Fixes the build AND brings socrates into the monorepo
  standard in one change.
- **Cons:** Larger diff.

### Recommendation: Option C

Aligns socrates with the rest of the monorepo. Specifically:

1. **Commit a stub** `socrates/src/socrates/_version.py`:
   ```python
   __version__ = "0.0.0.dev0"
   githash = ""
   datestamp = "0.0.0.dev0"
   ```
   (or whatever the equivalent of the existing `make version` output
   is — currently the Makefile uses `0.0.0.dev$(VERSION)` where
   `VERSION = 0.0.0.dev$(date +%Y%m%d%H%M)`.)

2. **Update `socrates/Makefile:17-18`** to match the `bed/src/Makefile:30-34`
   pattern:
   ```make
   version:
   	@echo '__version__ = "0.0.0.dev$(VERSION)"' > src/socrates/_version.py
   	@echo 'githash = "'`git log -1 --format='%H' 2>/dev/null | cut -c 1-16`'"' >> src/socrates/_version.py
   	@echo 'datestamp = "$(VERSION)"' >> src/socrates/_version.py
   	@cat src/socrates/_version.py
   ```
   (Note: pick `datestamp` not `__datestamp__` — the zoid6 reference
   scheme, see the cross-references below.)

3. **Fix the stale `bbsengine6>=9.0` constraint** in
   `socrates/pyproject.toml:29`. Replace with `bbsengine6>=0.0.1.dev202606`
   to match `bed/pyproject.toml:16` (the post-2026-06-26 value). The
   build error above prevents this from being verifiable until
   step 1 lands.

4. **Verify** with:
   ```bash
   cd /home/opencode/data/work/socrates
   cd src && make -C .. version  # regenerate _version.py
   pip install -e . --no-deps   # should now succeed
   pip check                     # socrates >=9.0 warning should be gone
   ```

### Decisions

- [ ] Pick Option A, B, or C. Recommendation: C.
- [ ] If C: commit stub `_version.py`, update Makefile, fix
      `>=9.0` constraint, verify with the four commands above.
- [ ] If A or B: minimal fix first; track Makefile + constraint
      updates as separate TODOs.

### Cross-references

- `asimov/TODO.md` "_version.py files: clean up orphans and
  standardize stamping" — the parent cross-monorepo cleanup.
- `zoid6/TODO.md` "Standardize `_version.py` symbol names across
  the monorepo" (this file, the section above) — covers the
  `__datestamp__` vs `datestamp` rename and lists socrates as one
  of the projects that uses `__version__` only (no `githash`,
  no `datestamp`). The socrates build-error fix is a prerequisite
  for any future socrates work.
- `socrates/Makefile:3` — uses `0.0.0.dev$(VERSION)`; the
  monorepo standard (bed/zoid6/bbsengine6/empyre/murdermotel) is
  `0.0.1.dev$(VERSION)`. Flag for a follow-up normalization.
- `socrates/pyproject.toml:29` — `bbsengine6>=9.0` is stale;
  post-2026-06-26 should be `bbsengine6>=0.0.1.dev202606`.
- `bbsengine6/pyproject.toml:53` — the dynamic-attr pattern that
  socrates mirrors; this is the pattern that requires `_version.py`
  to exist at build time, hence the socrates build error.

---



**Context (2026-08-22 incident).** `/home/jam/.venv/lib/python3.12/site-packages/`
holds five `__editable__.zoid6-*.pth` files from previous `pip install -e` runs
(timestamps: 202608131710, 202608200826, 202608200902, 202608201240,
202608201703). Each one imports a unique finder module that registers
`_EditableFinder` against `sys.meta_path`. The finder's `install()` is idempotent
(only appends if not already present), so they don't break anything today — but:

- `pip uninstall zoid6` will leave stale state in `sys.path`/`.pth` files that
  confuse subsequent `pip install` and `pip-tools` operations.
- Running `pip list` shows `zoid6 0.0.1.dev202608201703` even though the
  underlying source tree has been edited since 202608201703.
- Future contributors who follow the "editable install" pattern from this
  README will end up with the same five-file pile.

Sibling packages (`bed`, `casino`, `bbsengine6`) likely show the same pattern —
same root cause, same fix.

### [ ] Consolidate to a single editable install per package

For each affected package (`zoid6`, and any of `bed`/`casino`/`bbsengine6`
showing the same five-file pile):

      ```bash
      # Capture which version is currently active so we can
      # verify nothing changes semantically:
      pip show <pkg> | grep -E '^(Version|Location)'
      python -c "import <pkg>; print(<pkg>.__file__)"

      # Remove the stale .pth files and the dist-info:
      pip uninstall -y <pkg>
      rm -f /home/jam/.venv/lib/python3.12/site-packages/__editable__.<pkg>-*.pth
      rm -f /home/jam/.venv/lib/python3.12/site-packages/__editable___<pkg>_*_finder.py
      rm -f /home/jam/.venv/lib/python3.12/site-packages/__pycache__/__editable___<pkg>*.pyc
      rm -rf /home/jam/.venv/lib/python3.12/site-packages/<pkg>-*.dist-info

      # Reinstall editable from the live source tree:
      pip install -e /home/opencode/data/work/<pkg>
      pip show <pkg> | grep -E '^(Version|Location)'
      python -c "import <pkg>; print(<pkg>.__file__)"
      ```

Resolution: only one `__editable__.<pkg>-*.pth` file remains per package; `pip
list` shows the current editable version; `python -c "import <pkg>.main;
print(<pkg>.main.__file__)"` still points at the live source tree; `<pkg>`
launches successfully.

Verification:

- [ ] `ls /home/jam/.venv/lib/python3.12/site-packages/__editable__.zoid6-*.pth`
      returns exactly one file per affected package.
- [ ] `python -c "import zoid6.main; print(zoid6.main.__file__)"` prints
      `/home/opencode/data/work/zoid6/src/zoid6/main.py`.
- [ ] `zoid6` (and each remediated sibling) launches without import errors.
- [ ] Repeat for `bed`, `casino`, `bbsengine6` if any show the same five-file
      pile — same root cause, same fix.
