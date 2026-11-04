# zoid6 TODO: Session config (bed side)

Owner: bed (bed.json session section and config tests)
Open: 6

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---

- [ ] Update `bed/src/bed/data/bed.json` - add `session` section with `expiry_minutes`

- [ ] `bed/tests/test_session_config.py` - Config tests
  - [ ] test_session_section_exists
  - [ ] test_session_expiry_minutes_default
  - [ ] test_session_config_loads
  - [ ] test_env_override_session
