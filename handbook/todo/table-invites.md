# zoid6 TODO: Table invites and join rules

Owner: casino (table flags); bbsengine6 owns engine.__invite
Open: 21   Done: 0

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---

### Invite-Only Tables
- [ ] Add `invite_only` flag to table creation
- [ ] Add `invite_code` field - owner sets a code when creating invite-only table
- [ ] When joining invite-only table, user must provide correct `invite_code`
- [ ] Add `invite` message type to invite a user to a table (sends code to user)
- [ ] Only users with correct code can join invite-only tables
- [ ] Table owner can manage invites and regenerate codes

### Module Updates (remove module-specific tables)
- [ ] Remove `casino.__table_invite` (use `engine.__invite` instead)
- [ ] Update casino to use `bbsengine6.invite` module
- [ ] Each module implements module-specific validation logic
- [ ] Each module implements message types: `create_invite`, `list_invites`, `revoke_invite`
- [ ] Update `casino/dal/table.py:update_table()` to support new flags:
  - `invite_only` (boolean)
  - `invite_code` (string)
  - `require_approval` (boolean)
  - `hidden` (boolean)
- [ ] Update related service handlers and commands to pass new flags

### Join Approval
- [ ] Add `require_approval` flag to table creation
- [ ] When enabled, each join request requires owner/sysop approval
- [ ] Add `approve_join` / `reject_join` message types
- [ ] Owner receives notification of pending join requests

### Hidden Tables
- [ ] Add `hidden` flag to table creation
- [ ] Hidden tables don't appear in `list_tables`
- [ ] Users must know exact table name to join
- [ ] Sysops always see hidden tables in list (with indicator)
- [ ] Sysops can join hidden tables without approval
