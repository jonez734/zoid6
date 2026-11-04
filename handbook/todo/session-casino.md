# zoid6 TODO: Session migration (casino)

Owner: casino (Phase 4)
Open: 25

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---

### Phase 4: casino Updates

- [ ] Import `bbsengine6.services.session` instead of local SessionManager (Note: in-memory base `bbsengine6.session.core.SessionManager` already extracted; only DB-backed `services/session.py` remains)
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
