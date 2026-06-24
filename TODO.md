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
