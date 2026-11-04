# zoid6 TODO: Session migration (empyre)

Owner: empyre (Phase 5)
Open: 23

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---

### Phase 5: empyre Updates

- [ ] Import `bbsengine6.services.session` instead of local SessionManager (Note: in-memory base `bbsengine6.session.core.SessionManager` already extracted; only DB-backed `services/session.py` remains)
- [ ] Create `PlayerServiceHandler` in `empyre/api/handler.py`
- [ ] Create `empyre/services/player.py` - PlayerService for empyre player data
  - [ ] Uses view `empyre.player` for SELECTs
  - [ ] Uses table `empyre.__player` for INSERTs/UPDATEs
- [ ] Add `player_service` to `MessageRouter`
- [ ] Message types: `player_profile`, `player_update`, `player_tier`, `player_referral_code`, `player_referrals` + empyre-specific
- [ ] Add `module: "empyre"` to all responses
- [ ] Update tests in `empyre/src/tests/test_api_handler.py`

#### Phase 5: Integration Tests

- [ ] Test PlayerServiceHandler handles player_profile message
- [ ] Test PlayerServiceHandler handles player_update message
- [ ] Test PlayerServiceHandler handles player_tier message
- [ ] Test PlayerServiceHandler handles player_referral_code message
- [ ] Test PlayerServiceHandler handles player_referrals message
- [ ] Test empyre player data (rank, turncount) is returned correctly
- [ ] Test response includes module: "empyre"
- [ ] Test empyre session uses bbsengine6.services.session
- [ ] Test module data (island_moniker) is stored correctly via set_module_data
- [ ] Test empyre player view is used for SELECTs
- [ ] Test empyre.__player table is used for INSERTs/UPDATEs
- [ ] Test channel subscriptions work with new session module
- [ ] Test empyre-specific player endpoints work (rank, combat stats, etc.)
- [ ] Test existing empyre tests pass with new PlayerServiceHandler

---
