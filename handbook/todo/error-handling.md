# zoid6 TODO: Error Handling

Owner: zoid6 router
Open: 12   Done: 0

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---


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
