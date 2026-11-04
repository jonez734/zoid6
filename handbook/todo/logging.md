# zoid6 TODO: Logging

Owner: zoid6 (log format), with bbsengine6 logentry
Open: 12   Done: 32

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---


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
- [x] All modules verified clean of `log_event`/`log_entry` definitions (casino, empyre, murdermotel, bed, zoid6, asimov, asb)
- [x] All bank tests pass (25 passed)

---
