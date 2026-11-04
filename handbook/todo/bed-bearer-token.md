# zoid6 TODO: BED bearer token

Owner: bed (owner); zoid6 xref
Open: 2   Done: 0

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---


- [ ] See `bed/TODO.md` "Bearer token" — adopt `bed.api.auth.AuthService` for BED-mode authentication and reconnect. Replaces per-game `auth` implementations. Zoid6 benefits strongly: dashboard / shared-wallet / chat clients that stay open for hours.
- [ ] **Override built-in services** — allow zoid6 to replace bed-native services (auth, member) with its own implementations. Add an `"override": true` flag in zoid6.json; when set, zoid6 skips the "must exist" RuntimeError, calls `server.unregister_service()` on the existing service, then loads and registers its own from `modulepath`.
