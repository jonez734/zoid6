# Zoid6 Project Plan

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

- [ ] Remove `bbsengine6/common.py` - delete duplicate logentry()
- [ ] Convert `bbsengine6/bank.py` to proper subpackage:
  - [ ] Create `bbsengine6/bank/__init__.py` - exports: Account, Transaction, Transfer, BankService
  - [ ] Create `bbsengine6/bank/account.py` - Account class
  - [ ] Create `bbsengine6/bank/transaction.py` - Transaction class
  - [ ] Create `bbsengine6/bank/transfer.py` - Transfer class
  - [ ] Delete old `bbsengine6/bank.py`
  - [ ] Update all imports that reference `bbsengine6.bank`
- [ ] Add WebSocket handler for bank:
  - [ ] Create `bbsengine6/bank/api/__init__.py`
  - [ ] Create `bbsengine6/bank/api/handler.py` - BankServiceHandler, MessageRouter
- [ ] Add log_event() calls to bank operations:
  - [ ] add_funds() - credit to account
  - [ ] remove_funds() - debit from account
  - [ ] transfer() - transfer request created
  - [ ] approve_transfer()
  - [ ] reject_transfer()

### Phase 4: empyre Package (ADD - already exists, add WebSocket handler)

- [ ] Add `empyre/src/empyre/api/__init__.py` - exists, add if needed
- [ ] Add `empyre/src/empyre/api/handler.py` - MessageRouter for empyre WebSocket services
- [ ] Keep existing `__init__.py` intact (existing BBS functionality)
- [ ] Create `empyre/pyproject.toml` if needed
- [ ] Create empyre test files

### Phase 5: mistermcfeely Package (postoffice) (ADD)

- [ ] Add `mistermcfeely/src/mistermcfeely/api/__init__.py` - exists, add if needed
- [ ] Add `mistermcfeely/src/mistermcfeely/api/handler.py` - MessageRouter for postoffice
- [ ] Move `casino/services/postoffice.py` to `mistermcfeely/services/`
- [ ] Create `mistermcfeely/pyproject.toml` if needed
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
- [ ] Make zoid6 installable via `pip install -e zoid6/src`
- [ ] Create zoid6 test files

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

---

## Logging

### log_event() Function
- [ ] Create `log_event()` function in each module
- [ ] Build on `bbsengine6.util.logentry()`
- [ ] Include parameters: moniker, loginid, ip_address, fingerprint, module, action, table, details, level

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

## What bbsengine6 Provides (Built-in)

- `bbsengine6.services.member` - profile, tier, referrals
- `bbsengine6.bank` - banking services
- `bbsengine6.services.auth` - authentication
- `bbsengine6.services.channel` - pub/sub messaging
