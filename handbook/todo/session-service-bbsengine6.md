# zoid6 TODO: Session service (bbsengine6)

Owner: bbsengine6 (services/session.py, session.sql, tests)
Open: 47

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---

- [ ] Update `bbsengine6/sql/session.sql` - add `module` column to `engine.__session`

### Phase 2: bbsengine6 Session Module

- [ ] Create `bbsengine6/services/session.py` - functional WebSocket session module
  - [ ] `register_session(args, session_id, moniker, module, is_sysop)`
  - [ ] `unregister_session(args, session_id)`
  - [ ] `get_session(args, session_id)` - checks expiry, refreshes
  - [ ] `get_moniker(args, session_id)`
  - [ ] `get_module(args, session_id)`
  - [ ] `get_is_sysop(args, session_id)`
  - [ ] `set_module_data(args, session_id, key, value)`
  - [ ] `get_module_data(args, session_id, key)`
  - [ ] `add_to_collection(args, module, collection_name, key, session_id)`
  - [ ] `remove_from_collection(args, module, collection_name, key, session_id)`
  - [ ] `get_collection(args, module, collection_name, key)`
  - [ ] `garbage_collect(args)`


- [ ] `bbsengine6/py/tests/test_session_schema.py` - Schema tests
  - [ ] test_module_column_exists
  - [ ] test_session_insert_with_module
  - [ ] test_session_select_module
  - [ ] test_session_update_module
  - [ ] test_session_view_includes_module
- [ ] `bbsengine6/py/tests/test_session_service.py` - Service tests
  - [ ] test_register_session
  - [ ] test_register_duplicate
  - [ ] test_unregister_session
  - [ ] test_get_session_valid
  - [ ] test_get_session_expired
  - [ ] test_get_session_not_found
  - [ ] test_get_moniker
  - [ ] test_get_module
  - [ ] test_get_is_sysop
  - [ ] test_set_module_data
  - [ ] test_get_module_data
  - [ ] test_add_to_collection
  - [ ] test_get_collection
  - [ ] test_remove_from_collection
  - [ ] test_garbage_collect
  - [ ] test_expiry_refreshed_on_access
  - [ ] test_full_session_lifecycle
  - [ ] test_module_isolation
  - [ ] test_collection_tracks_sessions

#### Phase 3: Integration Tests

- [ ] Test session persists across multiple get_session calls
- [ ] Test session expiry is extended on each access
- [ ] Test garbage_collect removes only expired sessions
- [ ] Test module data is isolated between different modules
- [ ] Test collection operations work correctly with multiple sessions
- [ ] Test bed.json session config is loaded correctly by bed daemon
- [ ] Test session module integrates with bbsengine6 database module
