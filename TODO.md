# Zoid6 Project Plan

## Overview

Create a modular architecture where each BBS module (casino, empyre, murdermotel, postoffice, bank) can run independently or together via a unified router.

## Architecture

```
bed --router <module>.api.handler.MessageRouter
```

The `bed` daemon loads a router class dynamically via the `--router` argument. Each module provides its own MessageRouter that registers its services.

## Projects to Create/Modify

### 1. bed (NEW)

Location: `/home/opencode/data/work/bed/`

Daemon infrastructure - runs WebSocket server and loads router dynamically.

```
bed/
├── src/
│   └── bed/
│       ├── __init__.py
│       ├── bed.py              # Daemon: args, autorestart, signals, loads router dynamically
│       ├── config.py           # load_config(), reload_config() for bed.json
│       ├── data/
│       │   └── bed.json        # Default config (from casino)
│       ├── api/
│       │   ├── __init__.py
│       │   └── handler.py      # SessionManager, BaseService
│       └── tests/
│           └── test_bed.py     # (from casino)
└── pyproject.toml              # Entry point: bed command
```

Key changes from current casino/bed.py:
- Uses `--router` arg like bbsengine6/bed.py
- Loads router dynamically
- No hardcoded module services

### 2. casino (MODIFY)

Location: `/home/opencode/data/work/casino/src/casino/`

- Remove `bed.py`, `data/bed.json`, `tests/test_bed.py`
- Remove config functions from `config.py` (keep only casino-specific)
- **Keep `api/handler.py`** as-is (full MessageRouter with all casino services)

### 3. bbsengine6 (MODIFY)

Location: `/home/opencode/data/work/bbsengine6/py/src/bbsengine6/`

Create bank subpackage:

```
bbsengine6/bank/
├── __init__.py
├── api/
│   ├── __init__.py
│   └── handler.py      # BankServiceHandler, MessageRouter
├── services/
│   └── ...             # (existing bank.py content)
└── __init__.py
```

### 4. empyre (NEW)

Location: `/home/opencode/data/work/empyre/src/empyre/`

```
empyre/src/empyre/
├── __init__.py
├── api/
│   ├── __init__.py
│   └── handler.py     # MessageRouter for empyre services
└── pyproject.toml
```

### 5. mistermcfeely (NEW)

Location: `/home/opencode/data/work/mistermcfeely/src/mistermcfeely/`

```
mistermcfeely/src/mistermcfeely/
├── __init__.py
├── api/
│   ├── __init__.py
│   └── handler.py     # MessageRouter for postoffice services
├── services/
│   └── postoffice.py  # (move from casino/services/postoffice.py)
└── pyproject.toml
```

### 6. murdermotel (NEW)

Location: `/home/opencode/data/work/murdermotel/src/murdermotel/`

```
murdermotel/src/murdermotel/
├── __init__.py
├── api/
│   ├── __init__.py
│   └── handler.py     # MessageRouter for murdermotel services
└── pyproject.toml
```

### 7. zoid6 (NEW)

Location: `/home/opencode/data/work/zoid6/src/zoid6/`

Unified router that combines all modules:

```
zoid6/src/zoid6/
├── __init__.py
├── api/
│   ├── __init__.py
│   └── handler.py     # Unified MessageRouter
└── pyproject.toml
```

**zoid6/api/handler.py**:

```python
from casino.api.handler import MessageRouter as CasinoRouter
from empyre.api.handler import MessageRouter as EmpyreRouter
from mistermcfeely.api.handler import MessageRouter as PostofficeRouter
from murdermotel.api.handler import MessageRouter as MurdermotelRouter
from bbsengine6.bank.api.handler import MessageRouter as BankRouter


class MessageRouter:
    """Unified router that combines all module routers."""

    def __init__(self, args):
        self.casino = CasinoRouter(args)
        self.empyre = EmpyreRouter(args)
        self.postoffice = PostofficeRouter(args)
        self.murdermotel = MurdermotelRouter(args)
        self.bank = BankRouter(args)

    def register_all(self, server):
        self.casino.register_all(server)
        self.empyre.register_all(server)
        self.postoffice.register_all(server)
        self.murdermotel.register_all(server)
        self.bank.register_all(server)
```

## What bbsengine6 Provides (Built-in)

- `bbsengine6.services.member` - profile, tier, referrals
- `bbsengine6.bank` - banking services
- `bbsengine6.services.auth` - authentication
- `bbsengine6.services.channel` - pub/sub messaging

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

## Implementation Order

1. Create `bed` project
2. Update `casino` (remove moved files)
3. Create `bbsengine6/bank/api/handler.py`
4. Create `empyre` Python package
5. Create `mistermcfeely` Python package  
6. Create `murdermotel` Python package
7. Create `zoid6` Python package with unified router

## Testing

### Comprehensive Integration Tests

Write/update tests for the modular architecture:

1. **bed Daemon Tests** (`bed/tests/test_bed.py`)
   - Test that `--router` argument loads correct router class
   - Test router registration with WebSocket server
   - Test service message handling
   - Test signal handling (SIGTERM, SIGHUP)

2. **Module Handler Tests** (per module)
   - Each module's `tests/test_handler.py`
   - Test MessageRouter initialization
   - Test service registration
   - Test message handling for each service
   - Test session management

3. **zoid6 Unified Router Tests** (`zoid6/tests/test_unified_router.py`)
   - Test all module routers are initialized
   - Test all services are registered with server
   - Test cross-module message routing
   - Test full integration with all modules together

4. **Integration Tests** (`zoid6/tests/test_integration.py`)
   - Test running `bed --router zoid6.api.handler.MessageRouter`
   - Test WebSocket connection and authentication
   - Test each module's services respond correctly
   - Test channel pub/sub across modules
   - Test bank transfers between casino and member accounts
   - Test postoffice mail delivery

5. **Test Fixtures**
   - Create shared fixtures for WebSocket server setup/teardown
   - Create database test pools for each module
   - Mock bbsengine6 services appropriately

6. **WebSocket Cleanup**
   - Ensure ALL tests properly close WebSocket connections
   - Add test fixtures that guarantee cleanup
   - Add assertions that verify server is stopped
   - Check for resource leaks in test teardown

## Error Handling

### Graceful Module Error Handling

The architecture should handle module failures gracefully without bringing down the entire system.

1. **Router Initialization Errors**
   - Catch import errors when loading router class via `--router`
   - Catch initialization errors in router `__init__`
   - Log clear error messages indicating which module failed and why

2. **Service Registration Errors**
   - Wrap each `router.register_all(server)` call in try/except
   - If one module's services fail to register, continue with others
   - Log which services failed to register

3. **Message Handling Errors**
   - Wrap individual service message handlers in try/except
   - Return error response to client instead of crashing
   - Log full traceback for debugging

4. **Runtime Recovery**
   - Allow modules to be reloaded without restarting entire daemon (via SIGHUP)
   - Implement health check endpoint to report which modules are active

## Logging

### Structured Event Logging

Create `log_event()` function in each module that builds on `bbsengine6.util.logentry()`:

```python
from bbsengine6.util import logentry, getcurrentloginid

def log_event(
    event_type: str,
    *,
    moniker: Optional[str] = None,
    loginid: Optional[str] = None,   # from getcurrentloginid()
    ip_address: Optional[str] = None,
    fingerprint: Optional[dict] = None,
    module: str,
    action: str,
    table: Optional[str] = None,
    details: Optional[dict] = None,
    level: str = "info",
) -> None:
    """Log a structured event. Builds on bbsengine6.util.logentry()."""
    # Build human-readable message
    # Format: [module] action key=value key=value free text at end
    parts = [f"[{module}]", action]
    if moniker:
        parts.append(f"moniker={moniker}")
    if loginid:
        parts.append(f"loginid={loginid}")
    if table:
        parts.append(f"table={table}")
    if details:
        parts.append(str(details))
    
    message = " ".join(parts)
    
    logentry(message, level=level)
```

### Log Format Standard

**Format:** `[module] action key=value key=value free_text_at_end`

- Key-value pairs go at the start (e.g., `moniker=john amount=100`)
- Free text goes at the end (no `reason=` prefix)
- Use underscores for multi-word fields (e.g., `loginid`, `accountid`)

**Example messages:**
```
[auth] login_success moniker=john loginid=jamie ip=192.168.1.1
[auth] login_failed moniker=john loginid=jamie ip=192.168.1.1 invalid password
[casino] joined moniker=john table=blackjack-1
[bank] credit accountid=5 moniker=john amount=1000 type=deposit by=casino
[bank] transfer_rejected transfer_id=42 accountid=7 by=jane insufficient funds
```

### Authentication Logging

1. **Log successful authentication**
   - Moniker
   - IP address
   - Any fingerprinting data available (user agent, etc.)
   - Timestamp
   - Session ID

2. **Log failed authentication attempts**
   - Attempted moniker
   - IP address
   - Failure reason
   - Timestamp

3. **Log deauthentication**
   - Log when user disconnects/logs out

### Module Event Logging

1. **Table/Game events**
   - "moniker joined table-1"
   - "moniker left table-1"
   - "moniker won $X with natural blackjack on blackjack-1"
   - "moniker bet $X on blackjack-1"
   - etc.

2. **Generic event structure**
   ```python
   log_event("game_action", moniker="...", action="joined", table="blackjack-1", details={...})
   ```

3. **Module-specific events** - Each module (casino, empyre, murdermotel, etc.) logs its own relevant events

### Log Output Examples

```
[auth] login_success moniker=john ip=192.168.1.1
[auth] login_failed moniker=john ip=192.168.1.1 reason=invalid_password
[casino] joined moniker=john table=blackjack-1
[casino] won moniker=john table=blackjack-1 {'hand': 'natural_blackjack', 'payout': 15}
```

### Bank Event Logging

Add `log_event()` calls to bank operations in `bbsengine6/bank.py`:

1. **add_funds()** - credit to account
   - accountid, moniker, amount, type, description, by

2. **remove_funds()** - debit from account
   - accountid, moniker, amount, type, description, by

3. **transfer()** - transfer request created
   - from_accountid, from, to_accountid, to, amount, by

4. **approve_transfer()**
   - transfer_id, accountid, by

5. **reject_transfer()**
   - transfer_id, accountid, by, reason (free text at end)

**Example messages:**
```
[bank] credit accountid=5 moniker=john amount=1000 type=deposit by=casino
[bank] debit accountid=5 moniker=john amount=50 type=bet by=casino
[bank] transfer from_accountid=5 from=john to_accountid=7 to=jane amount=100 by=john
[bank] transfer_approved transfer_id=42 accountid=7 by=jane
[bank] transfer_rejected transfer_id=42 accountid=7 by=jane insufficient funds
```

### Auth Ban/Kick Logging

1. **Log user bans**
   - moniker, by (sysop), duration, reason (free text at end)

2. **Log user unbans**
   - moniker, by (sysop)

3. **Log user kicks**
   - moniker, by (sysop), reason (free text at end)

4. **Log failed ban/unban/kick attempts**
   - moniker, by (sysop), reason (free text at end)

**Example messages:**
```
[auth] banned moniker=john by=sysopadmin duration=permanent cheating
[auth] unbanned moniker=john by=sysopadmin
[auth] kicked moniker=john by=sysopadmin abuse
[auth] ban_failed moniker=john by=sysopadmin already banned level=warn
```

## Code Review

### Robustness Review
1. **Error handling** - Check all routers handle import/initialization errors gracefully
2. **Type annotations** - Verify all handlers have proper type hints
3. **Null checks** - Ensure all optional values are checked before use
4. **Connection pooling** - Verify database connections are managed properly
5. **Resource cleanup** - Ensure WebSocket connections, db pools are closed on shutdown
6. **Logging** - Add appropriate logging at key decision points

### Clarity Review
1. **Docstrings** - Ensure all public classes/functions have docstrings
2. **Naming** - Verify consistent naming (MessageRouter, BaseService, etc.)
3. **Structure** - Confirm each module follows the same pattern (`api/handler.py`)
4. **Imports** - Check imports are clean and avoid circular dependencies
5. **Constants** - Extract magic numbers/strings to constants
6. **Comments** - Verify complex logic has explanatory comments

### Specific Files to Review
- `casino/api/handler.py` - 1200+ lines, may need splitting
- `bbsengine6/bed.py` - understand the existing pattern
- Each new module's `api/handler.py` - follow established patterns
