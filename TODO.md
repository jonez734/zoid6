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

- [ ] Create `/home/opencode/data/work/bed/` directory structure
- [ ] Create `bed/src/bed/__init__.py`
- [ ] Create `bed/src/bed/bed.py` - daemon with `--router` arg, loads router dynamically
- [ ] Create `bed/src/bed/config.py` - load_config(), reload_config() for bed.json
- [ ] Create `bed/src/bed/data/bed.json` - default config (move from casino)
- [ ] Create `bed/src/bed/api/__init__.py`
- [ ] Create `bed/src/bed/api/handler.py` - SessionManager, BaseService classes
- [ ] Create `bed/src/bed/tests/test_bed.py` (move from casino)
- [ ] Create `bed/pyproject.toml` with entry point
- [ ] Add comprehensive tests for bed daemon

### Phase 2: casino Updates

- [ ] Remove `casino/src/casino/bed.py`
- [ ] Remove `casino/src/casino/data/bed.json`
- [ ] Remove `casino/src/casino/tests/test_bed.py`
- [ ] Remove config functions from `casino/src/casino/config.py` (keep only casino-specific)
- [ ] Keep `casino/src/casino/api/handler.py` as-is

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

### Phase 4: empyre Package

- [ ] Create `/home/opencode/data/work/empyre/src/empyre/` directory
- [ ] Create `empyre/src/empyre/__init__.py`
- [ ] Create `empyre/src/empyre/api/__init__.py`
- [ ] Create `empyre/src/empyre/api/handler.py` - MessageRouter for empyre services
- [ ] Create `empyre/pyproject.toml`
- [ ] Create empyre test files

### Phase 5: mistermcfeely Package (postoffice)

- [ ] Create `/home/opencode/data/work/mistermcfeely/src/mistermcfeely/` directory
- [ ] Create `mistermcfeely/src/mistermcfeely/__init__.py`
- [ ] Create `mistermcfeely/src/mistermcfeely/api/__init__.py`
- [ ] Create `mistermcfeely/src/mistermcfeely/api/handler.py` - MessageRouter for postoffice
- [ ] Move `casino/services/postoffice.py` to `mistermcfeely/services/`
- [ ] Create `mistermcfeely/pyproject.toml`
- [ ] Create mistermcfeely test files

### Phase 6: murdermotel Package

- [ ] Create `/home/opencode/data/work/murdermotel/src/murdermotel/` directory
- [ ] Create `murdermotel/src/murdermotel/__init__.py`
- [ ] Create `murdermotel/src/murdermotel/api/__init__.py`
- [ ] Create `murdermotel/src/murdermotel/api/handler.py` - MessageRouter for murdermotel
- [ ] Create `murdermotel/pyproject.toml`
- [ ] Create murdermotel test files

### Phase 7: zoid6 Package

- [ ] Create `/home/opencode/data/work/zoid6/src/zoid6/` directory
- [ ] Create `zoid6/src/zoid6/__init__.py`
- [ ] Create `zoid6/src/zoid6/api/__init__.py`
- [ ] Create `zoid6/src/zoid6/api/handler.py` - Unified MessageRouter
- [ ] Create `zoid6/pyproject.toml`
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
- [ ] Create `casino.__table_invite` database table to persist invite codes:
  - tableid (FK to table)
  - code (the invite code)
  - createdbymoniker (moniker who created)
  - datecreated (timestamptz)
  - useddate (timestamptz when used, null if unused)
  - expiresdate (timestamptz optional expiration)
- [ ] Create view using creator's timezone (like memberview.sql pattern):
  ```sql
  create or replace view casino.table_invite as
      select
          i.*,
          timezone(m.tz, i.datecreated) as datecreatedlocal,
          timezone(m.tz, i.useddate) as useddatelocal,
          timezone(m.tz, i.expiresdate) as expiresdatelocal
      from casino.__table_invite as i
      left outer join engine.__member as m on m.moniker = i.createdbymoniker
  ;
  ```

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
