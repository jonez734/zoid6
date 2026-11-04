# zoid6 TODO: History and record sections

Owner: zoid6 (record only; open items point to owners)
Open: 5   Done: 47

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---

## Implementation Tasks

### Phase 1: bed Project

- [X] Create `/home/opencode/data/work/bed/` directory structure
- [X] Create `bed/src/bed/__init__.py`
- [X] Create `bed/src/bed/main.py` - daemon with `--router` arg, loads router dynamically
- [X] Create `bed/src/bed/lib.py` - buildargs() for bbsengine6 consistency (main.py imports lib.py)
- [X] Create `bed/src/bed/config.py` - load_config(), reload_config() for bed.json
- [X] Create `bed/src/bed/data/bed.json` - default config (move from casino)
- [X] Create `bed/src/bed/api/__init__.py`
- [X] Create `bed/src/bed/api/handler.py` - SessionManager, BaseService classes
- [X] Create `bed/src/bed/tests/test_bed.py` (move from casino)
- [X] Create `bed/pyproject.toml` with entry point
- [X] Add comprehensive tests for bed daemon (21 tests)

### Phase 2: casino Updates

- [X] Remove `casino/src/casino/bed.py`
- [X] Remove `casino/src/casino/data/bed.json`
- [X] Remove `casino/src/casino/tests/test_bed.py`
- [X] Remove config functions from `casino/src/casino/config.py` (keep only casino-specific)
- [X] Keep `casino/src/casino/api/handler.py` as-is
- [ ] Known failing tests (pre-existing, not Phase 2 related):
  - `test_blackjack_flow.py::TestBlackjackFullFlow::*` (10 tests) - database not running
  - `test_commands.py::TestSubcommandResolution::test_game_resolve_ambiguous`
  - `test_new_features_integration.py::TestSurrenderDisabled::test_surrender_disabled`
  - `test_table_maint.py::TestListTables::test_list_tables_includes_status` - DAL bug

### Phase 3: bbsengine6 Updates

- [X] Remove `bbsengine6/common.py` - delete duplicate logentry()
- [X] Convert `bbsengine6/bank.py` to proper subpackage:
  - [X] Create `bbsengine6/bank/__init__.py` - exports: Account, Transaction, Transfer, BankService
  - [X] Create `bbsengine6/bank/account.py` - Account class
  - [X] Create `bbsengine6/bank/transaction.py` - Transaction class
  - [X] Create `bbsengine6/bank/transfer.py` - Transfer class
  - [X] Delete old `bbsengine6/bank.py`
  - [X] Update all imports that reference `bbsengine6.bank`
- [X] Add WebSocket handler for bank:
  - [X] Create `bbsengine6/bank/api/__init__.py`
  - [X] Create `bbsengine6/bank/api/handler.py` - BankServiceHandler, MessageRouter
- [X] Add log_event() calls to bank operations:
  - [X] add_funds() - credit to account
  - [X] remove_funds() - debit from account
  - [X] transfer() - transfer request created
  - [X] approve_transfer()
  - [X] reject_transfer()

### Phase 4: empyre Package (ADD - already exists, add WebSocket handler)

- [X] Add `empyre/src/empyre/api/__init__.py` - exists, add if needed
- [X] Add `empyre/src/empyre/api/handler.py` - MessageRouter for empyre WebSocket services
- [X] Keep existing `__init__.py` intact (existing BBS functionality)
- [X] Create `empyre/pyproject.toml` if needed
- [X] Create empyre test files

### Phase 6: murdermotel Package (ADD)

- [ ] Add `murdermotel/src/murdermotel/api/__init__.py` - exists, add if needed
- [ ] Add `murdermotel/src/murdermotel/api/handler.py` - MessageRouter for murdermotel
- [ ] Create `murdermotel/pyproject.toml` if needed
- [ ] Create murdermotel test files

### Phase 7: zoid6 Package

- [X] Create `/home/opencode/data/work/zoid6/src/zoid6/` directory
- [X] Create `zoid6/src/zoid6/__init__.py`
- [X] Create `zoid6/src/zoid6/api/__init__.py`
- [X] Create `zoid6/src/zoid6/api/handler.py` - Unified MessageRouter
- [X] Create `zoid6/src/zoid6/data/zoid6.json` - service configuration
- [X] Use bbsengine6.module.get() in bed.py for router loading
- [X] Create `zoid6/Makefile` for development commands
- [X] Make zoid6 installable via `pip install -e zoid6/src`
- [X] Create zoid6 test files

**Implemented 2026-06-25:** Phase 7 complete. Added `zoid6/src/Makefile` with install/test/lint/clean targets. zoid6 is installable editable via `pip install -e zoid6/src`. Test suite: 42 tests in `zoid6/src/zoid6/tests/` (conftest.py, test_config.py, test_message_router.py) — all passing. Tests cover MessageRouter init/config load, register_all skipping disabled services, error handling (ImportError, AttributeError, generic), bbsengine6.module.load usage, get_config/get_enabled_services helpers, and bed.json schema validation.

---

## Session plan: post-bring-up cleanup (2026-06-28)

The bed+casino bring-up session on 2026-06-28 discovered four
pre-existing bugs and one missing feature across the
zoid6, bed, and casino repos. The bring-up used a
workaround (`UPDATE engine.__member SET credits=1000 WHERE
credits IS NULL` and `--bed-secret /home/opencode/.config/bed/dev.secret`)
that is no longer needed once the fixes land.

This entry is the consolidated session plan and serves as
the index for the per-repo implementation tasks. The per-
repo tasks already exist in the `## \`--pidfile\` PID file
management` (bed TODO), `## \`data/zoid6.json\` — \`bank\` and
\`channel\` modulepaths do not resolve` (zoid6 TODO),
`## zoid6 unified router: bank modulepath collision` (zoid6
TODO), and `## \`zoid6.api.handler.MessageRouter\` does not
register \`list_services\`` (zoid6 TODO) sections above.
Read this section for the cross-repo picture; read the
section above for the per-repo task list.

### Bugs found and status

1. **casino `dal/player.py:92` crashes on `NULL` credits**
   — **FIXED** in `casino/03be20c casino: read NULL credits
   as 0 in get_player_balance and place_bet`. The bring-up
   workaround (`UPDATE engine.__member SET credits=1000 WHERE
   credits IS NULL`) is no longer needed. The casino TODO
   was rewritten to focus on the per-hand money-flow rework
   plan in `casino/8b3417e`; this entry is now superseded.

2. **`zoid6/src/zoid6/data/zoid6.json` `bank` and `channel`
   `modulepath`s do not resolve** — open. Fix path:
   `bank.modulepath` → `bbsengine6.bank.api.handler`;
   `channel.enabled` → `false` (channel service does not
   exist yet). See the
   `## \`data/zoid6.json\` — \`bank\` and \`channel\`
   modulepaths do not resolve` and
   `## zoid6 unified router: bank modulepath collision`
   sections above for tasks.

3. **`bed/src/bed/main.py:101` `_apply_auth_config` tilde
   bug** — open. Fix path: wrap the JSON value in
   `os.path.expanduser` at three call sites
   (`_apply_bind_config`, `_apply_database_config`,
   `_apply_auth_config`). See the
   `## \`_apply_auth_config\` overwrites CLI \`--bed-secret\`
   with literal \`~\`` section in `bed/TODO.md` for tasks.

4. **`zoid6/src/zoid6/api/handler.py:13`
   `MessageRouter` does not register `list_services`** —
   open. Fix path: add a `ListServicesService` class,
   register it first in `MessageRouter.register_all`. See
   the `## \`zoid6.api.handler.MessageRouter\` does not
   register \`list_services\`` section above for tasks.

### Missing feature

5. **`bed` `--pidfile` CLI arg exists but is never
   written** — open. Fix path: write the pidfile at the
   top of `bed/src/bed/main.py:main_async`, remove it in
   a `try/finally` around the autorestart loop. See the
   `## \`--pidfile\` PID file management` section in
   `bed/TODO.md` for tasks.

### Execution plan (7 commits across 4 repos)

When green-lit, the fixes land in this order:

1. **`bed`**: tilde fix (3 lines in `_apply_*_config` +
   regression tests in `bed/tests/test_bed.py::TestConfigFlag`).
2. **`zoid6`**: `list_services` handler (1 new class + 1
   new method + 1 test in
   `zoid6/src/zoid6/tests/test_bed_startup.py`).
3. **`zoid6`**: bank/channel JSON fix (2 lines in
   `zoid6/src/zoid6/data/zoid6.json` + assertion list update
   in `zoid6/src/zoid6/tests/test_config.py`).
4. **`casino`**: delete the superseded `--pidfile` entry
   in the `## BED (BBS Engine Daemon) Improvements`
   section (no code change in casino).
5. **`bed`**: add `## \`--pidfile\` PID file management`
   section to `bed/TODO.md` (between "CLI `--config` flag"
   and "Systemd deployment" sections).
6. **`bed`**: pidfile lifecycle implementation —
   `bed/src/bed/main.py:main_async` write/remove +
   `bed/src/bed/tests/test_bed.py::TestPidfile` +
   `bed/tests/scripts/stop_bed.sh` + `bed/README.md` "PID
   file" subsection.
7. **End-to-end verification**: re-run
   `pytest zoid6/src/zoid6/tests/ -q` and
   `pytest bed/src/bed/tests/ -q` in
   `/home/opencode/data/work/.venv312`; restart bed with
   `--pidfile /tmp/bed-test.pid --bed-secret
   /home/opencode/.config/bed/bed.secret` and verify the
   pidfile lifecycle, the 56+ message types from
   `list_services`, the absence of `Failed to import bank`
   / `Failed to import channel` lines, and that
   `--bed-secret` is honored.

### Pre-execution cleanup (Step 0)

Before any commit lands, four bed processes were racing
on `127.0.0.1:8765` via `SO_REUSEPORT` (pids 3668500,
3797060, 3802229, 3803184). The user's prior approval
covers `kill 3797060 3802229 3803184` (SIGTERM, graceful)
to leave only pid 3668500 (the user's intended instance)
listening. The signal handler at
`bed/src/bed/main.py:370-373` calls
`asyncio.create_task(bed.stop())` on SIGTERM, which
awaits `self.server.stop()` and releases the port.

### Cross-references

- `bed/TODO.md` "## `--pidfile` PID file management" —
  per-repo tasks for the pidfile work (Steps 4b-4f).
- `bed/TODO.md` "## `_apply_auth_config` overwrites CLI
  `--bed-secret` with literal `~`" — per-repo tasks for
  the tilde fix (Step 1).
- `zoid6/TODO.md` "## `data/zoid6.json` — `bank` and
  `channel` modulepaths do not resolve" — per-repo
  tasks for the JSON fix (Step 3).
- `zoid6/TODO.md` "## zoid6 unified router: bank
  modulepath collision (file as separate task)" — the
  separately-filed bank task (Step 3, option b chosen).
- `zoid6/TODO.md` "## `zoid6.api.handler.MessageRouter`
  does not register `list_services`" — per-repo tasks
  for the handler addition (Step 2).
- `casino/TODO.md` (entry deleted per Step 4a) — the
  casino TODO no longer tracks the `--pidfile` work;
  bed is the single source of truth.
- `casino/03be20c casino: read NULL credits as 0 in
  get_player_balance and place_bet` — the casino-side
  fix for the NULL credits crash (already landed).

---
