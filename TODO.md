# Zoid6 Project Plan

## BED bearer token

- [ ] See `bed/TODO.md` "Bearer token" — adopt `bed.api.auth.AuthService` for BED-mode authentication and reconnect. Replaces per-game `auth` implementations. Zoid6 benefits strongly: dashboard / shared-wallet / chat clients that stay open for hours.

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
- [X] Create `zoid6/src/zoid6/data/bed.json` - service configuration
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
- [ ] Update `zoid6/src/zoid6/data/bed.json` - add `session` section

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

- [ ] Import `bbsengine6.services.session` instead of local SessionManager
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

- [ ] Import `bbsengine6.services.session` instead of local SessionManager
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
  --config /opt/zoid6/src/zoid6/data/bed.json \
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

### Follow-up: bed CLI in this repo's CI
Any CI for zoid6 that runs `make test` should also
`pip install -e ../bed/src` first (or whatever layout the deploy
uses) so the new `bed>=0.0.1.dev2026` dep resolves. The current
test invocation in this repo's `Makefile` (`pytest zoid6/tests`)
only imports zoid6, so the import-time check passes; the runtime
declaration is enough to keep pip happy during install.
