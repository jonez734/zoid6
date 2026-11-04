# zoid6 TODO: Generic invite code system and channel access

Owner: bbsengine6 (engine.__invite, channel access)
Open: 30   Done: 0

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---

### Generic Invite Code System (bbsengine6)
- [ ] Create `bbsengine6/sql/invite.sql` - shared invite code table:
  ```sql
  CREATE TABLE engine.__invite (
      id bigserial primary key,
      module text not null,  -- 'casino', 'empyre', 'murdermotel', etc.
      resourceid text not null,  -- tableid, islandid, roomid, etc.
      code text not null,
      createdbymoniker citext constraint fk_invite_createdby references engine.__member(moniker) on update cascade on delete set null,
      datecreated timestamptz,
      dateused timestamptz,
      dateexpires timestamptz,
      -- Module-specific FKs for referential integrity:
      constraint fk_invite_casino foreign key (resourceid) references casino.__table(moniker) on delete cascade
  );
  -- Add similar FKs as modules are added:
  -- constraint fk_invite_empyre foreign key (resourceid) references empyre.__island(id) on delete cascade
  ```
- [ ] Create `engine.invite` view with local timezone conversion
- [ ] Create `bbsengine6/invite.py` - DAL functions:
  - `create_invite(args, module, resourceid, createdbymoniker, dateexpires)` → returns code
  - `get_invites(args, module, resourceid)` → list of codes
  - `revoke_invite(args, invite_id)` → bool
  - `validate_invite(args, module, resourceid, code)` → returns invite record or None
  - `mark_used(args, invite_id)` → bool

### Membership Invites (module='member')
- [ ] Add `module='member'` to `engine.__invite` for general BBS invites
- [ ] Message types:
  - `member_invite_create` - create invite code
  - `member_invite_list` - list your invite codes
  - `member_invite_revoke` - revoke unused code
  - `member_invite_use` - use code during registration
- [ ] Track referrer (who invited) and invitee (new member)
- [ ] Keep extensible for future reward system

### Channel Access Control

#### Invite-Only Channels
- [ ] Add `invite_only` flag to channel creation
- [ ] Add invite code validation for joining invite-only channels
- [ ] Use generic `engine.__invite` system for channel invites
- [ ] Only invited users can subscribe to invite-only channels
- [ ] Channel owner can manage invites

#### Moderated Channels
- [ ] Add `moderated` flag to channel creation
- [ ] Add `moderators` list to channel (monikers)
- [ ] Add `member_moderation` config (boolean):
  - true: members' messages require approval
  - false: members can post freely (default)
- [ ] Add `non_member_moderation` config (boolean):
  - true: non-members' messages require approval
  - false: non-members can post freely (default)
- [ ] Add both fields to channel schema
- [ ] Implement moderation logic based on sender's membership status
- [ ] Add message types: `approve_message`, `reject_message`
- [ ] Moderators receive notification of pending messages

#### Private Channels
- [ ] Add `private` flag to channel creation
- [ ] Private channels don't appear in `get_subscriptions` list
- [ ] Users must know exact channel name to join
- [ ] Only invited/approved users can subscribe
- [ ] Sysops see private channels in list (with indicator)

#### Announce-Only Channels
- [ ] Add `announce_only` flag to channel creation
- [ ] Add `announcers` list to channel (monikers who can post)
- [ ] Only annoucers can send messages to the channel
- [ ] Anyone can subscribe and read (viewers)
- [ ] Sysops are always announcers by default
