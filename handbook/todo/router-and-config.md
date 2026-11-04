# zoid6 TODO: Router and config

Owner: zoid6
Open: 15   Done: 2

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---


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
  `test_disabled_services_skipped` assertion list currently only
  includes `auth`, `member`, `empyre`, `murdermotel`. Add `bank`
  and `channel` to the list when Option A lands.
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



`zoid6/src/zoid6/main.py:_resolve_config_path()` is the canonical,
intentional config-resolution chain for the `zoid6` console script:

1. `$ZOID6_CONFIG` env var (override; e.g. testing, sandboxed runs)
2. `/etc/zoid6/zoid6.json` (FHS-installed config; the production
   path on systemd hosts where `make install-etc` has run)
3. the packaged `src/zoid6/data/zoid6.json` shipped in the wheel
   (the dev-box path; no `sudo install-etc` required)

The resolver landed in `7ffb7c0` ("zoid6: run from venv via
pyproject console script") and has been the design intent since.
This section records it as the canonical fallback so a future
contributor doesn't "fix" the no-config case by making `--config`
required again. The resolver is also documented in `README.md`
(Quick start, lines 30–41) and `SPEC.md` (§1, §3.1).

### Cross-reference to bed

The same pattern lives in
[`bed/src/bed/_configpath.py:resolve_config_path`](../bed/src/bed/_configpath.py)
(added in commit `0ba05b2` "feat(bed): --config resolves to
wheel-shipped default when no flag is passed"). bed exposes the
chain explicitly because bed's `--config` flag is mandatory at
the CLI layer; zoid6 hides it because the `zoid6` console script
is the public entry point and should never prompt for a config
path.

### Why this matters

The `zoid6.tui` deploy target (`make -C zoid6/src deploy-tui` →
`install` → `pip install -e .`) is non-sudo **specifically
because** the resolver falls back to the packaged default. Without
the resolver, dev installs would crash at first invocation with
`No such file: /etc/zoid6/zoid6.json`. With it, `pip install -e .`
is a complete no-sudo dev workflow — matching the cross-monorepo
"devenv can run every service without `sudo install-etc`" goal.

`zoid6.prod` (the sudo umbrella) still installs
`/etc/zoid6/zoid6.json` via `make install-etc` so operators can
customise the config without rebuilding the wheel — the resolver
just makes the no-customisation case work without sudo.



`bbsengine6.startup` (`python -m bbsengine6.startup`) runs
`stage_zero → checkcreatedb → checkdatabase`, which already handles
"target database does not exist yet" by opening a pool against the
`postgres` maintenance DB and issuing `CREATE DATABASE "<args.databasename>"`
(`bbsengine6/py/src/bbsengine6/backend/checkdatabase.py:33-61`).
This means zoid6 does **not** need its own `zoid6.startup` module
that creates the `zoid6` database.

The two responsibilities are separated by design (see
`zoid6/SPEC.md §9`):

- **bbsengine6** owns DB lifecycle: connect, create if missing,
  schema, grants, privileges.
- **zoid6** owns routing and config: `zoid6.json:database.name`
  picks which DB the router talks to; the router assumes that DB
  is already bootstrapped (or that `python -m bbsengine6.startup`
  has just been run).

### Tasks

- [x] Verified: `bbsengine6.backend.checkdatabase` already creates
  the DB if missing. No `zoid6.startup` module is planned. (2026-08-19)
- [x] `bbsengine6.startup.main` now self-heals on first invocation
  by running a pre-flight against the `postgres` maintenance DB
  before any stage runs. `python -m zoid6.main` on a fresh host
  bootstraps the target DB on the first call to `bed.main.ensure_startup`,
  so the explicit two-step order (`python -m bbsengine6.startup` first,
  then `python -m zoid6.main`) is now best-effort guidance rather
  than a hard requirement. The two-step order is still recommended
  in deployment scripts for clarity; see `zoid6/SPEC.md §9` for
  the updated lifecycle description. (2026-08-20)

### Cross-references

- `bbsengine6/TODO.md` — "Make `python -m bbsengine6.startup`
  self-heal when the target database does not yet exist" (done
  2026-08-20 — pre-flight in `startup/main.py`).
- `bbsengine6/py/src/bbsengine6/backend/checkdatabase.py:33-61`
  — existing create-if-missing logic.
- `bbsengine6/py/src/bbsengine6/backend/checkcreatedb.py:38-86`
  — privilege probe that runs before the create attempt.
- `zoid6/SPEC.md §9` — "Database schema — bbsengine6 ships the
  `engine.*` schema and all migrations; this repo ships none."

---



- `bbsengine6.services.member` - profile, tier, referrals
- `bbsengine6.bank` - banking services
- `bbsengine6.services.auth` - authentication
- `bbsengine6.services.channel` - pub/sub messaging
- `bbsengine6.services.session` - WebSocket session management

---
