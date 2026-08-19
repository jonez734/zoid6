# Zoid6 Project Plan

## BED bearer token

- [ ] See `bed/TODO.md` "Bearer token" — adopt `bed.api.auth.AuthService` for BED-mode authentication and reconnect. Replaces per-game `auth` implementations. Zoid6 benefits strongly: dashboard / shared-wallet / chat clients that stay open for hours.
- [ ] **Override built-in services** — allow zoid6 to replace bed-native services (auth, member) with its own implementations. Add an `"override": true` flag in zoid6.json; when set, zoid6 skips the "must exist" RuntimeError, calls `server.unregister_service()` on the existing service, then loads and registers its own from `modulepath`.

## Console-script shebang alignment (deferred — apply bed/casino/zoid6 pattern)

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
- [ ] **mistermcfeely** — 3 console scripts (in
      `mistermcfeely/src/pyproject.toml`).
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

## BED `Sink` integration with `bbsengine6.io` (cross-project)

## BED `Sink` integration with `bbsengine6.io` (cross-project)

- [ ] See `bbsengine6/TODO.md` "`bbsengine6.io` sink infrastructure
  for thin-client BED conversion" (Phases 0–5) — the
  `bbsengine6.io.sink.Sink` protocol, `set_io_sink` /
  `reset_io_sink` contextvar API, `bbsengine6.io.echo_render` and
  `mci.parse` / `mci.render` public functions, and the
  `MessageRouter` / `MessageRouterMixin` in
  `bbsengine6/net/router.py`.
- [ ] See `bed/TODO.md` "BED `Sink` integration with
  `bbsengine6.io`" — the `BEDSink` (per-connection adapter in
  `bed/sinks/bed_sink.py`) and the `on_connect_hook` installation
  point on the `WebSocketServer`. Zoid6's `key_f2`
  session-level new-messages query is the "dashboard
  notifications" indicator (see `bed/TODO.md` "`key_f2` —
  session-level new-messages query" for the per-game adoption).
- [ ] **No code change required for the zoid6 `MessageRouter` in
  v1.** Zoid6 can opt into `MessageRouterMixin` later (a one-line
  change to its class declaration:
  `class MessageRouter(MessageRouterMixin):`) to gain the session
  API. v1 ships without the mixin; the BED sink infrastructure
  works against the bare zoid6 `MessageRouter`.
- [ ] **Backward compat**: door mode (no `BEDSink` installed) uses
  the default `DefaultSink` behavior, which is the current
  `bbsengine6.io` behavior byte-for-byte. The
  `bbsengine6/tests/test_io_backward_compat.py` suite passes for
  the zoid6 door-mode pytest corpus.

## Overview

Create a modular architecture where each BBS module (casino, empyre, murdermotel, postoffice, bank) can run independently or together via a unified router.

## Architecture

```
bed --router <module>.api.handler.MessageRouter
```

The `bed` daemon loads a router class dynamically via the `--router` argument. Each module provides its own MessageRouter that registers its services.

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
- [X] Fix `test_postoffice_config.py` tests that relied on removed functions
- [ ] Known failing tests (pre-existing, not Phase 2 related):
  - `test_blackjack_flow.py::TestBlackjackFullFlow::*` (10 tests) - database not running
  - `test_commands.py::TestSubcommandResolution::test_game_resolve_ambiguous`
  - `test_new_features_integration.py::TestSurrenderDisabled::test_surrender_disabled`
  - `test_postoffice_background_task.py` (2 tests) - database/mocking issues
  - `test_postoffice_imap_polling.py` (5 tests) - database/mocking issues
  - `test_postoffice_manual_check.py` (2 tests) - database/mocking issues
  - `test_postoffice_notification.py` (8 tests) - database/mocking issues
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

### Phase 5: mistermcfeely Package (postoffice) (ADD)

- [X] Add `mistermcfeely/src/postoffice/api/__init__.py` - exists, add if needed
- [X] Add `mistermcfeely/src/postoffice/api/handler.py` - MessageRouter for postoffice
- [X] Move `casino/services/postoffice.py` to `mistermcfeely/services/` (already in mistermcfeely/src/postoffice/service.py)
- [X] Create `mistermcfeely/pyproject.toml` if needed (already exists)
- [ ] Create mistermcfeely test files

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

## Table Access Control

### Invite-Only Tables
- [ ] Add `invite_only` flag to table creation
- [ ] Add `invite_code` field - owner sets a code when creating invite-only table
- [ ] When joining invite-only table, user must provide correct `invite_code`
- [ ] Add `invite` message type to invite a user to a table (sends code to user)
- [ ] Only users with correct code can join invite-only tables
- [ ] Table owner can manage invites and regenerate codes

### Generic Invite Code System (bbsengine6)
- [ ] Create `bbsengine6/sql/invite.sql` - shared invite code table:
  ```sql
  CREATE TABLE engine.__invite (
      id bigserial primary key,
      module text not null,  -- 'casino', 'empyre', 'murdermotel', etc.
      resourceid text not null,  -- tableid, islandid, roomid, etc.
      code text not null,
      createdbymoniker citext constraint fk_invite_createdby references engine.__member(moniker) on update cascade on delete set null,
      datecreated timestamptz,
      dateused timestamptz,
      dateexpires timestamptz,
      -- Module-specific FKs for referential integrity:
      constraint fk_invite_casino foreign key (resourceid) references casino.__table(moniker) on delete cascade
  );
  -- Add similar FKs as modules are added:
  -- constraint fk_invite_empyre foreign key (resourceid) references empyre.__island(id) on delete cascade
  ```
- [ ] Create `engine.invite` view with local timezone conversion
- [ ] Create `bbsengine6/invite.py` - DAL functions:
  - `create_invite(args, module, resourceid, createdbymoniker, dateexpires)` → returns code
  - `get_invites(args, module, resourceid)` → list of codes
  - `revoke_invite(args, invite_id)` → bool
  - `validate_invite(args, module, resourceid, code)` → returns invite record or None
  - `mark_used(args, invite_id)` → bool

### Module Updates (remove module-specific tables)
- [ ] Remove `casino.__table_invite` (use `engine.__invite` instead)
- [ ] Update casino to use `bbsengine6.invite` module
- [ ] Each module implements module-specific validation logic
- [ ] Each module implements message types: `create_invite`, `list_invites`, `revoke_invite`
- [ ] Update `casino/dal/table.py:update_table()` to support new flags:
  - `invite_only` (boolean)
  - `invite_code` (string)
  - `require_approval` (boolean)
  - `hidden` (boolean)
- [ ] Update related service handlers and commands to pass new flags

### Membership Invites (module='member')
- [ ] Add `module='member'` to `engine.__invite` for general BBS invites
- [ ] Message types:
  - `member_invite_create` - create invite code
  - `member_invite_list` - list your invite codes
  - `member_invite_revoke` - revoke unused code
  - `member_invite_use` - use code during registration
- [ ] Track referrer (who invited) and invitee (new member)
- [ ] Keep extensible for future reward system

### Join Approval
- [ ] Add `require_approval` flag to table creation
- [ ] When enabled, each join request requires owner/sysop approval
- [ ] Add `approve_join` / `reject_join` message types
- [ ] Owner receives notification of pending join requests

### Hidden Tables
- [ ] Add `hidden` flag to table creation
- [ ] Hidden tables don't appear in `list_tables`
- [ ] Users must know exact table name to join
- [ ] Sysops always see hidden tables in list (with indicator)
- [ ] Sysops can join hidden tables without approval

### Channel Access Control

#### Invite-Only Channels
- [ ] Add `invite_only` flag to channel creation
- [ ] Add invite code validation for joining invite-only channels
- [ ] Use generic `engine.__invite` system for channel invites
- [ ] Only invited users can subscribe to invite-only channels
- [ ] Channel owner can manage invites

#### Moderated Channels
- [ ] Add `moderated` flag to channel creation
- [ ] Add `moderators` list to channel (monikers)
- [ ] Add `member_moderation` config (boolean):
  - true: members' messages require approval
  - false: members can post freely (default)
- [ ] Add `non_member_moderation` config (boolean):
  - true: non-members' messages require approval
  - false: non-members can post freely (default)
- [ ] Add both fields to channel schema
- [ ] Implement moderation logic based on sender's membership status
- [ ] Add message types: `approve_message`, `reject_message`
- [ ] Moderators receive notification of pending messages

#### Private Channels
- [ ] Add `private` flag to channel creation
- [ ] Private channels don't appear in `get_subscriptions` list
- [ ] Users must know exact channel name to join
- [ ] Only invited/approved users can subscribe
- [ ] Sysops see private channels in list (with indicator)

#### Announce-Only Channels
- [ ] Add `announce_only` flag to channel creation
- [ ] Add `announcers` list to channel (monikers who can post)
- [ ] Only annoucers can send messages to the channel
- [ ] Anyone can subscribe and read (viewers)
- [ ] Sysops are always announcers by default

### Bed Network Configuration

- [ ] Add `bind` option to bed.json config file
- [ ] Add `--bind` option to bed daemon CLI with support for:
  - `localhost` - resolves to all IPs for localhost (via DNS), binds to each
  - `127.0.0.1` - IPv4 localhost only
  - `::1` - IPv6 localhost only
  - `0.0.0.0` - all IPv4 interfaces
  - `::` - all IPv6 interfaces
  - Specific IP addresses
- [ ] Implement dynamic resolution:
  - When `localhost` is specified, resolve via socket.getaddrinfo() to get all IPs
  - Bind to each returned IP
  - User is responsible for proper DNS configuration (no filtering of results)
- [ ] Implement dual-stack binding:
  - For each resolved IP, create a WebSocket server socket
  - All sockets share the same router/services
  - Handle connection distribution across all sockets
- [ ] Default to `localhost` for secure by default (binds to both IPv4/IPv6 localhost)
- [ ] Add validation for bind address format
- [ ] Handle "address already in use" errors gracefully
- [ ] Ensure client can connect to localhost regardless of whether it resolves to IPv4 or IPv6

---

## Testing

### bed Daemon Tests
- [ ] Test that `--router` argument loads correct router class
- [ ] Test router registration with WebSocket server
- [ ] Test service message handling
- [ ] Test signal handling (SIGTERM, SIGHUP)

### Module Handler Tests (per module)
- [ ] Test MessageRouter initialization
- [ ] Test service registration
- [ ] Test message handling for each service
- [ ] Test session management

### zoid6 Unified Router Tests
- [ ] Test all module routers are initialized
- [ ] Test all services are registered with server
- [ ] Test cross-module message routing
- [ ] Test full integration with all modules together

### Integration Tests
- [ ] Test running `bed --router zoid6.api.handler.MessageRouter`
- [ ] Test WebSocket connection and authentication
- [ ] Test each module's services respond correctly
- [ ] Test channel pub/sub across modules
- [ ] Test bank transfers between casino and member accounts
- [ ] Test postoffice mail delivery

### Test Fixtures & Cleanup
- [ ] Create shared fixtures for WebSocket server setup/teardown
- [ ] Create database test pools for each module
- [ ] Mock bbsengine6 services appropriately
- [ ] Ensure ALL tests properly close WebSocket connections
- [ ] Add test fixtures that guarantee cleanup
- [ ] Add assertions that verify server is stopped
- [ ] Check for resource leaks in test teardown

---

## Error Handling

- [ ] Catch import errors when loading router class via `--router`
- [ ] Catch initialization errors in router `__init__`
- [ ] Log clear error messages indicating which module failed and why
- [ ] Wrap each `router.register_all(server)` call in try/except
- [ ] If one module's services fail to register, continue with others
- [ ] Log which services failed to register
- [ ] Wrap individual service message handlers in try/except
- [ ] Return error response to client instead of crashing
- [ ] Log full traceback for debugging
- [ ] Allow modules to be reloaded without restarting entire daemon (via SIGHUP)
- [ ] Implement health check endpoint to report which modules are active
- [ ] Check if a MessageRouter with the same modulepath has already been registered, warn/error if duplicate

---

## Logging

### log_event() Function
- [x] Use `bbsengine6.util.logentry()` directly — do not create module-local wrappers
- [x] Pass `module="<name>"` kwarg to identify source module
- [x] Pass `action="..."` kwarg to identify the event type
- [x] Pass additional context as kwargs: `moniker`, `loginid`, `ip_address`, `fingerprint`, `table`
- [x] First positional arg is the free-text message (goes at end of formatted output)

### Log Format Standard
**Format:** `[module] action key=value key=value free_text_at_end`

- [ ] Key-value pairs go at the start (e.g., `moniker=john amount=100`)
- [ ] Free text goes at the end (no `reason=` prefix)
- [ ] Use underscores for multi-word fields (e.g., `loginid`, `accountid`)

### Authentication Logging
- [ ] Log successful authentication (moniker, ip_address, loginid, session_id)
- [ ] Log failed authentication attempts (moniker, ip_address, reason)
- [ ] Log deauthentication (moniker)

### Auth Ban/Kick Logging
- [ ] Log user bans (moniker, by, duration, reason)
- [ ] Log user unbans (moniker, by)
- [ ] Log user kicks (moniker, by, reason)
- [ ] Log failed ban/unban/kick attempts (moniker, by, reason)

### Module Event Logging
- [ ] Log table/game events (joined, left, won, bet, etc.)
- [ ] Each module logs its own relevant events

### Module log_event() Cleanup

For each project below, check for use of `log_event()` (any module-defined wrapper) and convert to `bbsengine6.util.logentry()` directly. If the module has defined its own `log_event()`, remove it.

**bbsengine6:**
- [x] `bbsengine6/bank/bank.py:11` - remove `log_event()` definition
- [x] `bbsengine6/bank/bank.py:67` - refactor `add_funds_failed` (add_funds method)
- [x] `bbsengine6/bank/bank.py:87` - refactor `add_funds` success
- [x] `bbsengine6/bank/bank.py:113` - refactor `remove_funds_failed` (amount check)
- [x] `bbsengine6/bank/bank.py:118` - refactor `remove_funds_failed` (account not found)
- [x] `bbsengine6/bank/bank.py:122` - refactor `remove_funds_failed` (insufficient funds)
- [x] `bbsengine6/bank/bank.py:141` - refactor `remove_funds` success
- [x] `bbsengine6/bank/bank.py:166` - refactor `transfer` success (transfer_request)
- [x] `bbsengine6/bank/bank.py:175` - refactor `transfer` failure
- [x] `bbsengine6/bank/bank.py:191` - refactor `approve_transfer` success
- [x] `bbsengine6/bank/bank.py:201` - refactor `approve_transfer` failure
- [x] `bbsengine6/bank/bank.py:216` - refactor `reject_transfer` success
- [x] `bbsengine6/bank/bank.py:223` - refactor `reject_transfer` failure
- [x] Verify no external imports of `bbsengine6.bank.log_event` (confirmed: 0)

**casino:**
- [x] Check for `log_event`/`log_entry` usage (confirmed clean: 0 matches)

**empyre:**
- [x] Check for `log_event`/`log_entry` usage (confirmed clean: 0 matches)

**mistermcfeely:**
- [x] Check for `log_event` usage (confirmed clean: 0 matches)
- [x] Note: `audit_log.py` and `password_auth.py` use `log_entry_fn` callback parameter (DI for `bbsengine6.logentry`) — keep as-is, different pattern

**murdermotel:**
- [x] Check for `log_event`/`log_entry` usage (confirmed clean: 0 matches)

**bed:**
- [x] Check for `log_event`/`log_entry` usage (confirmed clean: 0 matches)

**zoid6:**
- [x] Check for `log_event`/`log_entry` usage (confirmed clean: 0 matches)

**asimov:**
- [x] Check for `log_event`/`log_entry` usage (confirmed clean: 0 matches)

**asb:**
- [x] Check for `log_event`/`log_entry` usage (confirmed clean: 0 matches)

### Phase 5 Complete
- [x] `bbsengine6.util.logentry()` enhanced with key-value params (module, action, moniker, loginid, ip_address, fingerprint, table, **kwargs)
- [x] Format: `[module] action key=value key=value free_text`
- [x] Backward compatibility preserved
- [x] `bbsengine6/bank/bank.py` refactored: `log_event()` removed, all 12 call sites use `logentry()` directly
- [x] All modules verified clean of `log_event`/`log_entry` definitions (casino, empyre, mistermcfeely, murdermotel, bed, zoid6, asimov, asb)
- [x] `mistermcfeely` keeps `log_entry_fn` callback DI pattern (intentional, different from `log_event` wrapper)
- [x] All bank tests pass (25 passed)

---

## Code Review

### Robustness Review
- [ ] Check all routers handle import/initialization errors gracefully
- [ ] Ensure ALL code has proper type annotations (classes, functions, variables)
- [ ] Verify all handlers have proper type hints
- [ ] Ensure all optional values are checked before use
- [ ] Verify database connections are managed properly
- [ ] Ensure WebSocket connections, db pools are closed on shutdown
- [ ] Add appropriate logging at key decision points

### Clarity Review
- [ ] Ensure all public classes/functions have docstrings
- [ ] Verify consistent naming (MessageRouter, BaseService, etc.)
- [ ] Confirm each module follows the same pattern (`api/handler.py`)
- [ ] Check imports are clean and avoid circular dependencies
- [ ] Extract magic numbers/strings to constants
- [ ] Verify complex logic has explanatory comments

### Security Review
- [ ] Check for SQL injection vulnerabilities (use parameterized queries)
- [ ] Verify authentication is enforced on protected endpoints
- [ ] Check for authorization bypass issues (users can only access their own data)
- [ ] Validate input sanitization on all user-provided data
- [ ] Check for XSS vulnerabilities in any string output
- [ ] Verify WebSocket connections are properly authenticated
- [ ] Check for rate limiting on sensitive operations
- [ ] Verify session management is secure
- [ ] Check that config files with secrets are not committed
- [ ] Review error messages don't leak sensitive information

### Specific Files to Review
- [ ] `casino/api/handler.py` - 1200+ lines, may need splitting
- [ ] `bbsengine6/bed.py` - understand the existing pattern
- [ ] Each new module's `api/handler.py` - follow established patterns

---

## Commands

```bash
# Run individual modules
bed --router casino.api.handler.MessageRouter
bed --router empyre.api.handler.MessageRouter
bed --router mistermcfeely.api.handler.MessageRouter
bed --router murdermotel.api.handler.MessageRouter
bed --router bbsengine6.bank.api.handler.MessageRouter

# Run all modules via zoid6
bed --router zoid6.api.handler.MessageRouter
```

---

## Session Module and PlayerService

### Phase 1: Database & Config

- [ ] Update `bbsengine6/sql/session.sql` - add `module` column to `engine.__session`
- [ ] Update `bed/src/bed/data/bed.json` - add `session` section with `expiry_minutes`
- [ ] Update `zoid6/src/zoid6/data/zoid6.json` - add `session` section

### Phase 2: bbsengine6 Session Module

- [ ] Create `bbsengine6/services/session.py` - functional WebSocket session module
  - [ ] `register_session(args, session_id, moniker, module, is_sysop)`
  - [ ] `unregister_session(args, session_id)`
  - [ ] `get_session(args, session_id)` - checks expiry, refreshes
  - [ ] `get_moniker(args, session_id)`
  - [ ] `get_module(args, session_id)`
  - [ ] `get_is_sysop(args, session_id)`
  - [ ] `set_module_data(args, session_id, key, value)`
  - [ ] `get_module_data(args, session_id, key)`
  - [ ] `add_to_collection(args, module, collection_name, key, session_id)`
  - [ ] `remove_from_collection(args, module, collection_name, key, session_id)`
  - [ ] `get_collection(args, module, collection_name, key)`
  - [ ] `garbage_collect(args)`

### Phase 3: Phase 1 Tests

- [ ] `bbsengine6/py/tests/test_session_schema.py` - Schema tests
  - [ ] test_module_column_exists
  - [ ] test_session_insert_with_module
  - [ ] test_session_select_module
  - [ ] test_session_update_module
  - [ ] test_session_view_includes_module
- [ ] `bed/tests/test_session_config.py` - Config tests
  - [ ] test_session_section_exists
  - [ ] test_session_expiry_minutes_default
  - [ ] test_session_config_loads
  - [ ] test_env_override_session
- [ ] `bbsengine6/py/tests/test_session_service.py` - Service tests
  - [ ] test_register_session
  - [ ] test_register_duplicate
  - [ ] test_unregister_session
  - [ ] test_get_session_valid
  - [ ] test_get_session_expired
  - [ ] test_get_session_not_found
  - [ ] test_get_moniker
  - [ ] test_get_module
  - [ ] test_get_is_sysop
  - [ ] test_set_module_data
  - [ ] test_get_module_data
  - [ ] test_add_to_collection
  - [ ] test_get_collection
  - [ ] test_remove_from_collection
  - [ ] test_garbage_collect
  - [ ] test_expiry_refreshed_on_access
  - [ ] test_full_session_lifecycle
  - [ ] test_module_isolation
  - [ ] test_collection_tracks_sessions

#### Phase 3: Integration Tests

- [ ] Test session persists across multiple get_session calls
- [ ] Test session expiry is extended on each access
- [ ] Test garbage_collect removes only expired sessions
- [ ] Test module data is isolated between different modules
- [ ] Test collection operations work correctly with multiple sessions
- [ ] Test bed.json session config is loaded correctly by bed daemon
- [ ] Test session module integrates with bbsengine6 database module

### Phase 4: casino Updates

- [ ] Import `bbsengine6.services.session` instead of local SessionManager (Note: in-memory base `bbsengine6.session.core.SessionManager` already extracted; only DB-backed `services/session.py` remains)
- [ ] Rename `MemberServiceHandler` → `PlayerServiceHandler`
- [ ] Change message types: `member_*` → `player_*`
  - [ ] `member_profile` → `player_profile`
  - [ ] `member_update` → `player_update`
  - [ ] `member_tier` → `player_tier`
  - [ ] `member_referral_code` → `player_referral_code`
  - [ ] `member_referrals` → `player_referrals`
- [ ] Change response types: `member_*_result` → `player_*_result`
- [ ] Add `module: "casino"` to all responses
- [ ] Update `MessageRouter.member_service` → `player_service`
- [ ] Update `register_all()` with new message types
- [ ] Update tests in `casino/src/casino/tests/test_member_services.py`

#### Phase 4: Integration Tests

- [ ] Test PlayerServiceHandler handles player_profile message
- [ ] Test PlayerServiceHandler handles player_update message
- [ ] Test PlayerServiceHandler handles player_tier message
- [ ] Test PlayerServiceHandler handles player_referral_code message
- [ ] Test PlayerServiceHandler handles player_referrals message
- [ ] Test response includes module: "casino"
- [ ] Test old member_* message types return error or are rejected
- [ ] Test casino session uses bbsengine6.services.session
- [ ] Test module data (table_moniker) is stored correctly via set_module_data
- [ ] Test channel subscriptions work with new session module
- [ ] Test GameServiceHandler continues to work with PlayerServiceHandler changes
- [ ] Test existing casino tests pass with new naming

### Phase 5: empyre Updates

- [ ] Import `bbsengine6.services.session` instead of local SessionManager (Note: in-memory base `bbsengine6.session.core.SessionManager` already extracted; only DB-backed `services/session.py` remains)
- [ ] Create `PlayerServiceHandler` in `empyre/api/handler.py`
- [ ] Create `empyre/services/player.py` - PlayerService for empyre player data
  - [ ] Uses view `empyre.player` for SELECTs
  - [ ] Uses table `empyre.__player` for INSERTs/UPDATEs
- [ ] Add `player_service` to `MessageRouter`
- [ ] Message types: `player_profile`, `player_update`, `player_tier`, `player_referral_code`, `player_referrals` + empyre-specific
- [ ] Add `module: "empyre"` to all responses
- [ ] Update tests in `empyre/src/tests/test_api_handler.py`

#### Phase 5: Integration Tests

- [ ] Test PlayerServiceHandler handles player_profile message
- [ ] Test PlayerServiceHandler handles player_update message
- [ ] Test PlayerServiceHandler handles player_tier message
- [ ] Test PlayerServiceHandler handles player_referral_code message
- [ ] Test PlayerServiceHandler handles player_referrals message
- [ ] Test empyre player data (rank, turncount) is returned correctly
- [ ] Test response includes module: "empyre"
- [ ] Test empyre session uses bbsengine6.services.session
- [ ] Test module data (island_moniker) is stored correctly via set_module_data
- [ ] Test empyre player view is used for SELECTs
- [ ] Test empyre.__player table is used for INSERTs/UPDATEs
- [ ] Test channel subscriptions work with new session module
- [ ] Test empyre-specific player endpoints work (rank, combat stats, etc.)
- [ ] Test existing empyre tests pass with new PlayerServiceHandler

---

## What bbsengine6 Provides (Built-in)

- `bbsengine6.services.member` - profile, tier, referrals
- `bbsengine6.bank` - banking services
- `bbsengine6.services.auth` - authentication
- `bbsengine6.services.channel` - pub/sub messaging
- `bbsengine6.services.session` - WebSocket session management

---

## Standardize `_version.py` symbol names across the monorepo

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

## `socrates` editable install fails: `ModuleNotFoundError: socrates._version`

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

## Build / packaging (zoid6 side)

Committed in `7a30ad5` (`zoid6: dynamic version, release Makefile
targets, bed dependency`). bed-side changes are recorded in
`bed/TODO.md` under "## Systemd deployment" and "## CLI `--config`
flag"; do not duplicate them here.

### zoid6 changes in this commit
- `pyproject.toml`: `version` is now `dynamic`, sourced from
  `zoid6._version.__version__`. `requires-python` aligned to
  `>=3.9,<3.13` per `specs/AGENTS.md` (mediapipe constraint). New
  runtime dep `bed>=0.0.1.dev2026` so the unified router can rely
  on the `bed` console script being installed alongside zoid6.
- `_version.py`: holds `__version__` (default `"0.0.0"`),
  `githash`, `datestamp`. Stamped by `make version`.
- `__init__.py`: re-exports `__version__` from `_version` so
  `zoid6.__version__` keeps working and the existing
  `tests/test_message_router.py::TestPackageMetadata::test_version_is_defined`
  still passes.
- `Makefile`: added `version`, `build`, `rename-sdist`, `sign`,
  `release` targets mirroring `empyre/src/Makefile`; preserved
  the existing dev/test targets.

### Follow-up: zoid6-bed.service
The systemd unit for zoid6-as-deployed-by-bed is **not** in this
repo. It will be added in a follow-up commit. The expected shape:
```
[Unit]
Description=zoid6 BBS Engine Daemon
After=network.target postgresql.service
Wants=postgresql.service

[Service]
Type=simple
User=bed
Group=bed
WorkingDirectory=/opt/bed
EnvironmentFile=-/etc/bed/bed.env

ExecStart=/usr/bin/bed \
  --config /opt/zoid6/src/zoid6/data/zoid6.json \
  --router zoid6.api.handler.MessageRouter \
  --no-autorestart

ExecReload=/bin/kill -HUP $MAINPID
Restart=on-failure
RestartSec=5s
KillMode=mixed
KillSignal=SIGTERM
TimeoutStopSec=30s
StandardOutput=journal
StandardError=journal
SyslogIdentifier=zoid6-bed

NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=yes
ReadWritePaths=/var/log/zoid6

[Install]
WantedBy=multi-user.target
```
Differences vs. the generic `bed.service` (in `bed/src/bed/daemon/bed.service`):
- `--config` explicitly points at the zoid6 unified-router config
  instead of using the packaged default.
- `--router zoid6.api.handler.MessageRouter` to load enabled zoid6
  module routers.
- `SyslogIdentifier=zoid6-bed` so `journalctl -u zoid6-bed` filters
  cleanly.
- `ReadWritePaths=/var/log/zoid6` (zoid6 has its own log dir).

This unit will be installed via a `make install-systemd` target
mirroring the one in `bed/src/Makefile` (i.e. copy
`zoid6/src/zoid6/daemon/zoid6-bed.service` to
`/etc/systemd/system/zoid6-bed.service` and `daemon-reload`).

### Prerequisite (added 2026-07-22)
The FHS plumbing for `/etc/zoid6/zoid6.json` and the tmpfiles
drop-in has landed first, so the draft unit above can resolve
its `--config` path:
- `zoid6/src/zoid6/daemon/zoid6.tmpfiles` (in-package, shipped
  in the wheel via `package-data`).
- `zoid6/src/zoid6/data/zoid6.json` is the canonical source;
  `make -C zoid6/src install-etc` rsyncs it from
  `usr/share/factory/etc/zoid6/` to `/etc/zoid6/zoid6.json`.
- `make -C zoid6/src install-tmpfiles` installs the tmpfiles
  drop-in to `/usr/lib/tmpfiles.d/zoid6.conf` and materializes
  `/etc/zoid6`.
- The draft unit above should reference `/etc/zoid6/zoid6.json`
  instead of `/opt/zoid6/src/zoid6/data/zoid6.json` once it ships.

### Ownership on `/etc/zoid6/` (added 2026-07-22)
All zoid6 FHS files are owned `root:root`:
- `/etc/zoid6/` → `755 root:root`
- `/etc/zoid6/zoid6.json` → `644 root:root`
- `/usr/lib/tmpfiles.d/zoid6.conf` → `644 root:root`

The `install-etc` and `install-tmpfiles` Makefile targets use
`sudo install -m 0644 -o root -g root …` (not `rsync`) so
ownership is set explicitly rather than inherited from the
source file. Both targets also run a follow-up
`sudo chown root:root …` as a self-heal, so re-running
`make -C src install-fhs` from a real-privilege shell fixes
ownership regardless of how a prior install landed it.

**Dev-box gotcha**: in containers with `NoNewPrivs` set
(e.g. this dev environment), `sudo install` runs as the
original user, so the files end up `opencode:opencode` on
first install. The follow-up `sudo chown` is *also* blocked
in that case. The directory `/etc/zoid6/` is created by
`systemd-tmpfiles` which honors the tmpfiles file's owner
column, so it lands `root:root` even in the no-privilege
container. The two files are fixed by re-running
`make -C src install-fhs` from a real-privilege shell — the
chown steps in the Makefile then execute for real.

### Follow-up: bed CLI in this repo's CI
Any CI for zoid6 that runs `make test` should also
`pip install -e ../bed/src` first (or whatever layout the deploy
uses) so the new `bed>=0.0.1.dev2026` dep resolves. The current
test invocation in this repo's `Makefile` (`pytest zoid6/tests`)
only imports zoid6, so the import-time check passes; the runtime
declaration is enough to keep pip happy during install.

---

## `data/zoid6.json` — `bank` and `channel` modulepaths do not resolve

### Problem

`zoid6/src/zoid6/data/zoid6.json` declares two services with
`enabled: true` whose `modulepath` strings do not import:

| Service | `modulepath` in `zoid6.json` | Reality |
|---|---|---|
| `bank` | `bank.api.handler` | The bank package lives at `bbsengine6/bank/api/handler.py`. As a top-level `bank`, the path is not importable. `MessageRouter._register_module` logs `Failed to import bank: No module named 'bank'`. The bank messages (`bank_balance` / `bank_add` / etc.) ARE wired on the wire — but by `bed.defaultrouter.DefaultRouter` (`bed/src/bed/defaultrouter.py:14`), **not** by casino's `MessageRouter`. |
| `channel` | `channel.api.handler` | No `channel` package exists in `bbsengine6`, no top-level `channel/` sibling project, no `channel/api/handler.py` anywhere in the tree. Logs `Failed to import channel: No module named 'channel'` at every startup. |

Net effect: the `bank` and `channel` enabled-true entries are dead
config — they never register anything on the `WebSocketServer` because
their modules don't exist. A reader of `zoid6.json` reasonably assumes
"if it's enabled, it works."

### Why this matters

- **Misleading config.** The `enabled: true` flag is the contract
  "this service runs at startup." Both currently lie.
- **Startup noise.** Every `bed` startup emits two traceback
  blocks (`importlib` failures) to the log, which obscures real
  startup errors.
- **Confusion for new modules.** A future contributor adding a
  real `channel` package to `bbsengine6` would reasonably expect
  `zoid6.json` to start picking it up; today it would, silently,
  if they also fix the `modulepath`. But the channel service
  itself is unimplemented (see `bbsengine6/TODO.md` Phase 7
  channel work), so flipping the `modulepath` to a not-yet-existing
  path is premature.
- **Bed's `DefaultRouter.register_all` already covers the
  bank-balance / transfer message types** (see
  `bed/src/bed/defaultrouter.py:14`, registering
  `bbsengine6.bank.api.handler.BankServiceHandler`; see also
  `bed/SPEC.md:137`). The `bank` entry in `zoid6/zoid6.json`
  was likely an early placeholder before bed absorbed the
  registration role. If a real `bbsengine6.bank.api` service
  is the desired replacement, the `modulepath` should be
  `bbsengine6.bank.api.handler` and the JSON's `enabled`
  flag should match the bed-side reality — i.e., flip the
  bed router's `bank_balance` wiring off before flipping the
  JSON's `enabled: true` (last-writer-wins).

### Fix options

#### Option A — disable both until they exist

Set `bank.enabled` and `channel.enabled` to `false` in
`zoid6/src/zoid6/data/zoid6.json`. Bed's
`DefaultRouter.BankServiceHandler` continues to register
`bank_*` messages; nothing changes at runtime. The JSON's
`services` section remains a complete roadmap of every service
zoid6 intends to host, but only the ones with working code
paths are flipped on.

- **Pros:** Smallest change. No code touched. Eliminates the
  misleading `enabled: true`. No startup noise.
- **Cons:** A future contributor has to remember to flip them
  on when the real modules land.

#### Option B — fix the `bank` modulepath, leave `channel` disabled

Change `zoid6/src/zoid6/data/zoid6.json` so `bank.modulepath` is
`bbsengine6.bank.api.handler` (the path that actually
resolves). `channel` stays disabled until the `channel`
service is implemented.

- **Pros:** The bank message types are registered from a single
  source (`bbsengine6.bank.api.handler.BankServiceHandler`,
  loaded by `bed.defaultrouter.DefaultRouter`). No casino-side
  handler exists to defer — casino's `MessageRouter` doesn't
  register bank messages at all.
- **Cons:** Requires deciding what happens when both register
  `bank_balance` — last writer wins, same as the casino vs.
  bed `auth` collision today. Currently casino's wins because
  it registers after bbsengine6's.

#### Option C — fix the `modulepath`s and de-duplicate registrations

Same as B for the JSON, plus a follow-up to make
`bbsengine6.bank.api.handler.MessageRouter.register_all`
idempotent (skip if `bank_balance` is already registered) so
the casino side can safely defer to it. Equivalent work for
`channel` when it lands.

- **Pros:** Cleanest end state. One source of truth for the
  bank message types.
- **Cons:** Cross-repo refactor (casino + bbsengine6 + zoid6
  all change).

### Recommendation: A for v1, B as a follow-up

`channel` does not exist as a module today, so it stays
disabled until the channel service lands. `bank` is a one-line
JSON fix to point at `bbsengine6.bank.api.handler`, but only
land it after deciding whether to keep the bed-side
`BankServiceHandler` (registered by `DefaultRouter`) as the
sole source, or also wire `zoid6.json`'s `bank` entry once its
`modulepath` is fixed (last-writer-wins concern).

### Tasks

- [ ] **Disable `bank` and `channel` in
  `zoid6/src/zoid6/data/zoid6.json`** (Option A). One-line each:
  `"enabled": false`.
- [ ] **Verify** by running
  `bed --router zoid6.api.handler.MessageRouter --debug` and
  confirming the `Failed to import bank` / `Failed to import
  channel` lines are gone from the startup log.
- [ ] **Update `zoid6/src/zoid6/tests/test_config.py`** — the
  `test_disabled_services_skipped` assertion list (line 121)
  currently only includes `auth`, `member`, `empyre`,
  `murdermotel`, `mistermcfeely`, `postoffice`. Add `bank` and
  `channel` to the list when Option A lands.
- [ ] **Update `zoid6/src/zoid6/tests/test_bed_startup.py`**
  `test_register_all_loads_every_enabled_service` and
  `test_register_all_does_not_load_disabled_services` — they
  currently derive the enabled set from the JSON dynamically,
  so they pick up the change automatically; no edit needed
  beyond a re-run.
- [ ] **Cross-reference to `bbsengine6/TODO.md`** Phase 7
  channel-service work — when that lands, flip
  `channel.enabled` to `true` and set `channel.modulepath`
  to the real path.
- [ ] **Open question (defer to zoid6/bed follow-up):** if
  `bbsengine6.bank.api.handler` is the desired single source
  of truth for bank message types, no casino-side change is
  needed (the casino router doesn't register bank). The zoid6
  JSON should just point its `bank.modulepath` at
  `bbsengine6.bank.api.handler` so the registration list
  reflects the same source bed already uses (see
  `bed/SPEC.md:137`).

**Future rework cross-reference:** the "Failed to import
bank" warning at startup will resolve naturally as part of
the casino's "Rework: route per-hand money through the bank"
plan (see `casino/TODO.md`). The zoid6 unified router's bank
modulepath must be loadable for that rework to land, since
the rework makes the bank load-bearing rather than
operator-only.

### Cross-references

- `zoid6/src/zoid6/tests/test_bed_startup.py` — the new
  `TestBedStartupIntegration` tests pass `modulepath="bank.api.handler"`
  to `bbsmodule.load` and assert `Failed to import bank` shows
  up in `capsys.readouterr()`. After Option A lands, the
  integration test still passes (the import is now skipped,
  not failed) but the assertion no longer matches the real
  log. Update the test to either assert no "Failed to
  import" lines for `bank`/`channel`, or drop the assertion
  entirely.
- `casino/src/casino/api/handler.py:1270` (`MessageRouter.register_all`)
  — casino's router enumerates 7 non-bank services (table, game,
  bet, chat, slot, yahtzee, tictactoe). It does **not** register
  a `BankServiceHandler`. The casino/bank cross-cutting concern
  lives in `casino.services.bank.BankService` (maintenance
  only) and `casino.commands.bank` (CLI gating via
  `bbsengine6.bank.access`).
- `bbsengine6/TODO.md` Phase 7 — channel service design.

**Note:** Bed-registration is the canonical source for bank message
wiring on the wire. See `bed/SPEC.md:137` for the definitive
bed-side wiring (`bed.defaultrouter.DefaultRouter` →
`bbsengine6.bank.api.handler.BankServiceHandler`).

---

## zoid6 unified router: bank modulepath collision (file as separate task)

`zoid6/src/zoid6/data/zoid6.json` declares a `bank` service
with `modulepath: "bank.api.handler"`, but the bank package
lives at `bbsengine6.bank`, not at the top-level `bank`
namespace. At zoid6 startup, this produces the warning:

```
Failed to import bank: No module named 'bank'
```

Bed's `DefaultRouter.BankServiceHandler` covers the bank
message types on the wire (registered by
`bed/src/bed/defaultrouter.py:14`; visible in the registered
services list at startup), so the warning is currently
non-fatal. But the bank service is `enabled: true` in
`zoid6.json` and is intended to be loaded by the zoid6
unified router; the failure is a real configuration
mismatch, not a benign log line.

This is filed as a **separate, independently-actionable
task** so it can be addressed without waiting on the larger
casino "Rework" plan. The two are related but not blocking
on each other.

### Tasks

- [ ] **Decide: top-level `bank` package vs
  `bbsengine6.bank` modulepath.** Either (a) ship a
  top-level `bank` package (perhaps a thin wrapper that
  re-exports `bbsengine6.bank` so the modulepath resolves),
  or (b) update `zoid6/src/zoid6/data/zoid6.json` to use
  `bbsengine6.bank.api.handler` as the modulepath.
- [ ] **Verify after fix.** `python -m bed.main --config
  /home/opencode/data/work/zoid6/src/zoid6/data/zoid6.json
  --router zoid6.api.handler.MessageRouter ...` should
  start with **no** "Failed to import bank" warning.
- [ ] **Document the resolution in the bank/handler entry
  above** (under "Open question" or as a new sub-bullet).

### Cross-references

- `bbsengine6/TODO.md` Phase 7 — bank package restructure.
- `casino/TODO.md` "Rework: route per-hand money through
  the bank" — the casino-side rework makes this warning
  load-bearing rather than noise, so resolving it is a
  prerequisite for that rework.

---

## `zoid6.api.handler.MessageRouter` does not register `list_services`

### Problem

`zoid6/src/zoid6/api/handler.py:13` `MessageRouter` loads every
enabled service from `data/zoid6.json` via
`MessageRouter.register_all`, but **does not register a
`list_services` handler itself.** A client that sends
`{"type": "list_services"}` to a bed instance fronted by the
zoid6 router gets back
`{"type": "error", "code": "no_handler", "message": "No handler for message type: list_services"}`.

The zoid6 router is the production path; `zoid6.json` ships
with it as the implicit default for any deployment that wants
the unified message set (auth, ping, list_tables, hit, stand,
bank_*, …). All of those work; `list_services` does not.

Compare with `bbsengine6.net.defaultrouter.DefaultRouter`
(`bbsengine6/py/src/bbsengine6/net/defaultrouter.py:15-17`),
which registers `["auth", "ping", "list_services"]`, and with
`zoid6.api.monikerrouter.MonikerAuthRouter`
(`zoid6/src/zoid6/api/monikerrouter.py:25-26`), which does
the same. The two smaller routers are developer-facing and
are expected to expose the discovery primitive; the unified
router isn't, but the asymmetry shows up the moment a
diagnostic tool (a `wscat` smoke script, the `casino-client`,
a bot, the `bed ping` console script in
`bed/src/bed/tools/ping.py`) tries to enumerate what's
actually registered.

### Why this matters

- **`bed ping`** (`bed/src/bed/tools/ping.py`) connects, sends
  `ping` (which works), then sends `list_services` to print
  the registered message types. Against a zoid6-router bed it
  fails with `no_handler`. The smoke test in
  `/tmp/bed-logs/smoke.py` (written during the bed+casino
  bring-up on 2026-06-28) hits this exact failure.
- **`casino-client` startup** at
  `casino/src/casino/client/casino_client.py:38-49` doesn't
  call `list_services` itself, but the user's
  `casino-client`-testing workflow often starts with
  `list_services` to confirm the server is the right one
  (zoid6 unified vs. bare casino vs. dev DefaultRouter).
- **General client UX.** Listing registered services is the
  standard "what can I send?" probe. Hiding it on the
  production router makes the system harder to debug.

### Fix options

#### Option A — register a `list_services` handler in the zoid6 `MessageRouter`

Add a tiny `ListServicesService` (or inline closure) to
`zoid6/src/zoid6/api/handler.py` that returns
`{"type": "services", "services": [k for k in
self.server._services.keys()]}` (matching the shape used by
`bbsengine6.net.defaultrouter.DefaultRouter._handle_list_services`).
Register it via
`self.server.register_service(self, ["list_services"])` in
`MessageRouter.register_all` (or via a dedicated
`register_default_services` method called first).

- **Pros:** Matches the behavior of the smaller routers.
  Zero new dependencies. One file changed.
- **Cons:** Slight duplication of the `defaultrouter`
  implementation. The `bbsengine6.net.WebSocketServer`
  already has a `list_services()` method
  (`bbsengine6/net/transport.py:497-504`) that returns the
  same data — the new handler is just a thin
  message-type wrapper around it.

#### Option B — move the `list_services` handler to `bbsengine6.net.WebSocketServer` itself

Have `WebSocketServer.dispatch_message` (or a new
`WebSocketServer.handle_list_services` method) answer
`list_services` directly, with no router involvement.

- **Pros:** Removes the per-router duplication. The
  `DefaultRouter` and `MonikerAuthRouter` could drop their
  `list_services` registrations.
- **Cons:** Cross-repo change (`bbsengine6`); affects every
  router, not just zoid6. Larger blast radius.

### Recommendation: A

The duplication is one method; A is the lower-risk change
and unblocks the `bed ping` / `casino-client` smoke
workflow today. B is the right long-term move but is its
own refactor.

### Tasks

- [ ] **Add a `ListServicesService` to
  `zoid6/src/zoid6/api/handler.py`** (Option A). Either as
  a tiny class or as a closure in
  `MessageRouter.register_all`. Wire shape matches
  `DefaultRouter._handle_list_services`:
  `{"type": "services", "services": [str, ...]}`.
- [ ] **Register it first** in `MessageRouter.register_all`
  (before any `bbsmodule.load` call) so it's available even
  if every enabled service in `zoid6.json` fails to import.
- [ ] **Update `zoid6/src/zoid6/tests/test_bed_startup.py`**
  `test_real_websocketserver_receives_registrations` to
  also assert `"list_services"` is in
  `server.list_services()` keys.
- [ ] **Update `test_real_server_has_casino_message_types`** —
  add `"list_services"` to the `expected_subset` set.
- [ ] **Re-run the smoke test** at `/tmp/bed-logs/smoke.py`
  and confirm `list_services` returns the 56-message-type
  list instead of `no_handler`.
- [ ] **Update `casino-client`** (or document the
  discovery) so a user can call `list_services` to confirm
  the server before authenticating.

### Cross-references

- `bbsengine6/py/src/bbsengine6/net/defaultrouter.py:15-58`
  — the reference implementation that already does this for
  the dev router. Copy its `services` shape verbatim.
- `zoid6/src/zoid6/api/monikerrouter.py:25-26` — the
  example router also registers `list_services`. Mirrors the
  same fix.
- `bed/src/bed/tools/ping.py` — the `bed ping` console
  script, which is the primary user of `list_services` in
  the CLI tooling.
- `casino/src/casino/client/casino_client.py:38-49` — the
  casino-client's connection entry point; the smoke-test
  workflow next to it expects `list_services` to work.

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

## Shared venv under `zoid6` (single venv for all bbsengine6 services)

zoid6 is the anchor package for a single shared venv at `/var/lib/zoid6/venv`, owned by a `zoid6` system user/group. All bbsengine6 services (bed, postoffice, casino, empyre, murdermotel, etc.) are installed into it so any service user can `import` any package. `deploytool` owns the topology — per-project Makefiles do not hard-code venv paths.

### Foundation (zoid6 repo)

- [x] Create `src/zoid6/daemon/zoid6.sysusers`:
  - [x] `u zoid6 - "zoid6 BBS Router" /var/lib/zoid6 -`
  - [x] `g zoid6 - -`
  - [x] `m bed zoid6` (bridge: bed service user joins the zoid6 group)
  - [x] `m postoffice zoid6` (bridge: postoffice service user joins the zoid6 group)
- [x] Replace `src/zoid6/daemon/zoid6.tmpfiles` with full layout:
  - [x] `d /etc/zoid6 0755 root root - -`
  - [x] `d /var/lib/zoid6 0755 zoid6 zoid6 - -`
  - [x] `d /var/lib/zoid6/venv 0755 zoid6 zoid6 - -`
  - [x] `d /var/log/zoid6 0755 zoid6 zoid6 - -`
- [x] Create `src/zoid6/daemon/zoid6.service` (templated, `@VENV_DIR@` placeholder):
  - [x] `User=zoid6`, `Group=zoid6`, `WorkingDirectory=/var/lib/zoid6`
  - [x] `ExecStart=@VENV_DIR@/bin/bed --config /etc/zoid6/zoid6.json --router zoid6.api.handler.MessageRouter` (`--router` added so zoid6 actually loads its unified router; bed CLI has no `--no-autorestart`)
  - [x] `SyslogIdentifier=zoid6`
- [x] Create `usr/share/factory/etc/zoid6/zoid6.json` (already existed — zoid6 router config; synced via `factory-sync`)
- [x] Update `src/Makefile`:
  - [x] Add `VENV_DIR ?= /var/lib/zoid6/venv`, `VENV_USER ?= zoid6`, `VENV_GROUP ?= zoid6`
  - [x] Add `install-sysusers` / `uninstall-sysusers` (install to `/usr/lib/sysusers.d/zoid6.conf`, `systemd-sysusers`)
  - [x] Add `install-venv` (idempotent: create venv if missing, `pip install --upgrade pip build setuptools wheel`; no wheel install — anchor only; depends on `install-sysusers`)
  - [x] Add `uninstall-venv` (`sudo rm -rf $(VENV_DIR)`)
  - [x] Add `install-systemd` / `uninstall-systemd` (`sed 's|@VENV_DIR@|$(VENV_DIR)|g'` at install time)
  - [x] Add `install-etc` / `uninstall-etc` for `/etc/zoid6/zoid6.json` from factory defaults
  - [x] Add `restorecon` (SELinux `bin_t` fcontext for `$(VENV_DIR)/bin(/.*)?`)
  - [x] Extend `install-fhs` chain: `install-sysusers install-tmpfiles install-venv install-systemd install-etc` (named `install-fhs` to avoid clobbering the existing editable `install` target used by `deploy-tui`)
  - [x] Add `uninstall-fhs` chain
  - [x] Extend `factory-sync` to also sync `zoid6.sysusers`
  - [x] Add `install-tmpfiles: install-sysusers` dependency (dirs owned by `zoid6:zoid6`)
- [x] Update root `Makefile`:
  - [x] Add `install-venv` / `uninstall-venv` / `install-fhs` / `uninstall-fhs` shims delegating to `src/Makefile` (the root `deploy` target is the PHP www deploy and was left untouched)

### deploytool (venv orchestration)

- [x] Add `VENV_LAYOUT` dict to `src/deploytool/lib.py` mapping each project → venv path (registry only; deploytool does not plumb venv vars into `make` — per-project Makefile defaults and `pip` handle venv targeting):
  - [x] Shared `/var/lib/zoid6/venv`: `zoid6`, `bed`, `mistermcfeely`, `bbsengine6`, `teos`, `murdermotel`, `empyre`, `casino`, `achilles`, `letteredolive`, `zoidoffice`
  - [x] `atlas` → `/var/lib/atlas/venv` (standalone placeholder)
  - [x] `backuptools`, `deploytool` → `VENV_USER` — the user's active venv (these tools are not part of zoid6; `getdate_next` and `asimov` also default to `VENV_USER`)
  - [x] Any unlisted project → `VENV_USER` (default)
- [x] Add `get_venv_layout(base)` helper; `VENV_USER` sentinel resolves at runtime to the active venv (`VIRTUAL_ENV`, else `sys.prefix` when it differs from `sys.base_prefix`); error if none active
- [x] No `--venv-dir`/`--venv-user`/`--venv-group` CLI flags and no pre-deploy venv-ensure step (dropped: `pip` targets the active venv automatically)
- [x] Add `install-venv` / `uninstall-venv` to the `backuptools` and `deploytool` Makefiles (consistency with the zoid6 anchor pattern; default `VENV_DIR` = active venv)

### mistermcfeely (postoffice)

- [x] Makefile: change `VENV_DIR = /var/lib/postoffice/venv` → `VENV_DIR ?= /var/lib/zoid6/venv`
- [x] Makefile: add `VENV_OWNER ?= zoid6`, `VENV_GROUP ?= zoid6`
- [x] Makefile: replace `sudo -u postoffice` with `sudo -u $(VENV_OWNER)` in `install-venv` / `restorecon`
- [x] Template `etc-postoffice/mcfeely-authd.service`: `ExecStart=@VENV_DIR@/bin/mcfeely-authd …` (keep `User=postoffice`, `WorkingDirectory=/var/lib/postoffice`)
- [x] Makefile `install-systemd`: `sed 's|@VENV_DIR@|$(VENV_DIR)|g'` when copying the unit
- [x] Update `install-venv` help text (shared venv)

### bed

- [x] Makefile: `VENV_DIR ?= /var/lib/zoid6/venv`, add `VENV_OWNER ?= zoid6`, `VENV_GROUP ?= zoid6`
- [x] Makefile: replace `sudo -u bed` with `sudo -u $(VENV_OWNER)`
- [x] Template `src/bed/daemon/bed.service`: `ExecStart=@VENV_DIR@/bin/bed --config /etc/bed/bed.json`
- [x] Makefile `install-systemd`: `sed 's|@VENV_DIR@|$(VENV_DIR)|g'`
- [x] Update `bed/FHS.md` with "Shared venv under `zoid6`" section

### Migration (deploy host, post-commit)

- [ ] Stop `mcfeely-authd` and `bed`
- [ ] Create the shared venv: `make install-sysusers install-tmpfiles install-venv` in `zoid6/src`
- [ ] Run `deploytool postoffice bed zoid6` to populate the shared venv
- [ ] Install templated units and `systemctl daemon-reload`
- [ ] Start `mcfeely-authd`, `bed`, `zoid6`
- [ ] Verify: `sudo -u bed /var/lib/zoid6/venv/bin/python -c "import postoffice"` (the original goal)
- [ ] Remove old per-service venvs: `rm -rf /var/lib/postoffice/venv /var/lib/bed/venv`
- [ ] Spot-check `bed.json` vs `zoid6.json` for port/bind conflicts (both run `bed` from the shared venv)

## Adopt deploytool-style `install: build` deploy workflow (cross-project)

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
      multi-wheel dependency dance (getdate_next + bbsengine6 +
      bed) are bed-specific. Trim the top-level `bed/Makefile`
      so the primary `install: build` target matches deploytool
      (install the just-built `bed-*.whl` into the active venv
      with `pip install --no-deps`), and keep the
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
- [ ] **postoffice** (if/when it has a Makefile) — apply the
      same pattern: `install: build` + `pip install --no-deps
      dist/postoffice-*.whl` + `deploy-tui: install` alias.
- [ ] **mistermcfeely** — `mistermcfeely/src/pyproject.toml`
      ships 3 console scripts; the Makefile (if any) should
      follow the same `build → install --no-deps → deploy-tui`
      chain for each of the 3 entry points.
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

## Standardize `OUTDIR` on `/srv/repo/<pkg>/` (cross-package)

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
- [ ] **postoffice**, **mistermcfeely**, **paymentflow**,
      **achilles**, **asimov**, **backuptools**, **moneyday**,
      **murdermotel**, **socrates**, **vulcan/tui**,
      **yummyjam/article2**, **zoidoffice** — when each is
      migrated to the deploytool `install: build` pattern per
      the section above, set `OUTDIR = /srv/repo/$(PROJECT)/`
      (not `dist/`) from the start.

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

