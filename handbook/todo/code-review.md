# zoid6 TODO: Code Review

Owner: zoid6 (reviews of zoid6 and the shared router)
Open: 26   Done: 0

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---


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
