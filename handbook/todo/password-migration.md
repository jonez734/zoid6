# zoid6 TODO: Password migration

Owner: zoid6 (done; kept as record)
Open: 0   Done: all (items are `### [x]` headings, not checkboxes)

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---


**Context (2026-08-22 incident).** `engine.__member.password` for
`jam` was holding a 34-char MD5-crypt hash (`$1$AUNKK0aN$...`,
prefix `$1$`) that survived an earlier `setpassword.py` run.
`checkpassword` issues `crypt('12345', stored)` which uses the
stored prefix to select the algorithm; with a `$1$` prefix that's
MD5-crypt, not bcrypt, so the comparison fails for any plaintext
that wasn't the one originally hashed. `bed auth login` returned
`Invalid moniker or password` consistently against this hash.
After re-running `setpassword.py` against the row, the column now
holds a proper bcrypt hash (length 60, prefix `$2a$`) and login
succeeds. The three follow-ups below are the cleanup so this
doesn't reoccur.

**Status (closed 2026-08-22):** all three items implemented across
`bbsengine6` (schema authority) and `zoid6` (operator workflow).
See `bbsengine6/TODO.md` for the schema-side cross-reference and
`bbsengine6/CHANGELOG.md` "[Unreleased] feat(bbsengine6): password
column hardening" for the implementation details.

### [x] Find and migrate any other legacy MD5-crypt hashes

Any other `engine.__member` row that was written by a pre-bcrypt
code path will reproduce the same symptom for that user. The
discovery query:

      ```sql
      SELECT moniker
        FROM engine.__member
       WHERE password IS NOT NULL
         AND password ~ '^\$1\$';
      ```

Each match needs either:

- (a) the member runs `setpassword.py <moniker> <newpassword>` to
      self-rotate, OR
- (b) a privileged bulk migration that wraps the existing
      plaintext in a bcrypt hash — but we don't have the plaintext
      for MD5-crypt rows, so this path is *not viable*; the legacy
      password must be considered lost and re-set by the user.

Resolution: zero rows match the regex. Pin a regression test in
`bbsengine6/py/tests/test_member_legacy_hash_audit.py` that runs
the query against the test DB (or skips with a clear marker if no
live DB) and asserts `len(rows) == 0`. The audit query itself
should be a documented module-level function in
`bbsengine6/member/lib.py` (e.g. `audit_password_column()`) so
both the test and any future CLI tool can call it.

### [x] Identify what wrote the MD5-crypt hash in the first place

A `$1$` hash in `engine.__member.password` is incompatible with
the Python-side `setpassword` (which writes bcrypt via
`crypt($1, gen_salt('bf'))` at `bbsengine6/member/lib.py:842`).
The most likely writers, in priority order:

- **`/srv/backups/work/zoid6/php/engine/`** — the legacy PHP
  bbsengine. PHP path predates the Python port and historically
  used MD5-crypt; if any PHP migration or restore ever ran
  against this DB, that's the vector. Search for `crypt(`,
  `md5(`, `password_hash` in PHP files.
- **`bbsengine6/sql/migrate_*.sql`** — every migration file.
  Grep `password` (case-insensitive) in `bbsengine6/sql/` and
  flag anything that writes `engine.__member.password` directly
  rather than via `setpassword`.
- **`bbsengine6/console/member.py:463`** — calls `setpassword`,
  so it should already write bcrypt; confirm there's no fallback
  branch that writes a literal hash.
- **Backup-restore / round-trip paths** — any tool that
  serializes and restores a member row should be either
  drop-and-reload (with the member re-setting the password) or
  transparent-rehash (rare and risky). Grep `backup`,
  `restore`, `dump` in `bbsengine6/scripts/` and
  `bbsengine6/backend/`.

Resolution: a one-paragraph comment at the top of
`bbsengine6/sql/member.sql` stating that `password` MUST be a
bcrypt hash (`$2a$` / `$2b$` / `$2y$` prefix, length 60) and
listing the writers that are known to violate that. Plus a
CHECK constraint or trigger that rejects `$1$` writes going
forward:

      ```sql
      -- Reject legacy MD5-crypt writes. Run once after the
      -- migration audit (item 1) is clean.
      alter table engine.__member
        add constraint chk_member_password_bcrypt
        check (password is null or password ~ '^\$2[abxy]\$');
      ```

The constraint belongs in a new file `bbsengine6/sql/manage_password_format.sql`
that's `\i`-included from `bbsengine6.sql` AFTER the member
table is created. Naming convention matches the existing
`sql/manage_role_privs.sql` precedent.

### [x] Auth-time password-field sanity check + diagnostic logging

When `member.checkpassword` runs (called from `bed auth login`,
`casino`'s auth prompt, `bed auth reconnect`, etc.) it queries
`engine.member.password` and runs the bcrypt round-trip silently.
If the column holds an unexpected value (NULL, empty string,
non-bcrypt prefix, length mismatch, malformed hash) the round-
trip returns no rows and `_handle_auth` returns `Invalid moniker
or password` with no hint of the actual problem — the operator
only sees the same failure mode as a wrong password. The
2026-08-22 incident took four `psql` probes to diagnose because
of this silence.

Add a `audit_password_hash(args, moniker, **kwargs)` helper in
`bbsengine6/member/lib.py` that:

  - SELECTs `engine.member.password` for the moniker.
  - Returns a structured report (namedtuple `PasswordHashAudit`
    with `present`, `non_empty`, `prefix`, `is_bcrypt`,
    `is_md5crypt`, `length_ok`).
  - `is_bcrypt`: prefix matches `^\$2[abxy]\$` (PG `crypt()`
    recognises `$2a$`, `$2b$`, `$2y$` as bcrypt; all are
    length 60).
  - `is_md5crypt`: prefix matches `^\$1\$` — the 2026-08-22
    incident trigger; legacy, must migrate.
  - Emits `io.echo(..., level="warning")` for any `False` health
    flag (`present`, `non_empty`, `is_bcrypt`, `length_ok`) or
    any `True` `is_md5crypt` flag. Operator-actionable signals.
  - Emits `io.echo(..., level="ok")` on the success path.
    `level="ok"` (alias `level="success"` per
    `bbsengine6/io/echo.py:1317`) renders with a green-background
    prefix and no letter — distinguishable from `level="warning"`
    at a glance, always visible (not gated by `--debug`; both
    `echo.py:1309-1318` and `output.py:381-388` render debug
    unconditionally). The distinction vs `level="debug"` is
    visual (positive vs investigative), not visibility-based.

Wire the audit into `member.checkpassword` (option A — covers
every caller including `bed auth login`, `bed auth reconnect`,
`casino`, and any future entry point):

  - `member.checkpassword` calls
    `audit_password_hash(args, moniker)` *before* the bcrypt
    round-trip SELECT.
  - On `is_md5crypt=True` it logs the warning AND continues to
    the round-trip anyway (don't refuse auth — the existing
    plaintext might still MD5-match the legacy hash; the warning
    is the operator signal to migrate, not a hard reject).
  - On all flags healthy, emit `level="ok"` so the operator
    sees a positive confirmation in the auth log without
    needing `--debug`.

Tests: `bbsengine6/py/tests/test_member_audit_password_hash.py`,
parametrized over (moniker, password value, expected flags,
expected log level). Minimum cases:

  - bcrypt hash → all flags True, `level="ok"` log only
  - MD5-crypt hash → `is_md5crypt=True` + `level="warning"` log
  - NULL → `present=False` + `level="warning"` log
  - empty string → `non_empty=False` + `level="warning"` log
  - 34-char non-prefixed text → `is_bcrypt=False`,
    `length_ok=False` + `level="warning"` log
  - 60-char text without `$2` prefix → `is_bcrypt=False` +
    `level="warning"` log

Resolution: every `member.checkpassword` caller logs a one-line
diagnostic on every auth attempt. The 2026-08-22 incident would
have produced a single
`W: jam password is MD5-crypt ($1$AUNKK0aN$...), migrate to bcrypt`
line at the first failed login instead of needing four `psql`
probes to diagnose. Successful auths render with the green
`level="ok"` prefix so a healthy auth path is visually
distinct from a silently-broken one.

Cross-ref: this section's items 1-2 ("find and migrate any other
legacy MD5-crypt hashes", "identify what wrote the
MD5-crypt hash"),
plus the standalone "Clean up stale editable-install `.pth` files"
section. The audit function is the runtime complement to those
one-shot scans — if a future code path reintroduces a `$1$`
hash, the audit logs the warning at the next auth attempt
instead of waiting for the operator to rediscover the
symptom.
