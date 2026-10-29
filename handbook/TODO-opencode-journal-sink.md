# TODO: opencode startup-log sink

Goal: let `opencode` read zoid6's startup lines without access to the journal.

Status: built and tested in bed and zoid6. Not yet deployed to minotaur; the operator steps below remain.

## Decisions

- Startup lines only go to a file. The journal is unchanged.
- The file path and rotation are set in `bed.json` (`startup_log`). `zoid6.json` carries the same keys.
- Access is through group `zoid6`. `opencode` joins that group, and the file is `0640`.
- Rotation is done by bed (`RotatingFileHandler`). Nothing is written to `/etc`.

## Config

`bed/src/bed/data/bed.json`:

```json
"startup_log": {
  "path": "/var/log/zoid6/startup.log",
  "max_bytes": 1048576,
  "backups": 5
}
```

An empty `path` turns the sink off. Keys are validated at load time.
`--startup-log PATH` overrides `path`.

## Steps

1. [x] bed: read `startup_log`, validate it, and attach a `RotatingFileHandler` to the `bbsengine6`
   logger for the startup `logentry()` calls only. Remove it on shutdown.
2. [x] bed: tests for off-by-default, startup-only lines, rotation, the flag override, and validation.
3. [x] zoid6: add the same `startup_log` section to `zoid6.json`. A test fails if its keys differ from
   `bed.json`.
4. [x] zoid6 unit (`zoid6.service`): add `UMask=0027`.
5. [x] zoid6 tmpfiles (`zoid6.tmpfiles`): `/var/log/zoid6` becomes `0750 zoid6 zoid6`.
6. [x] zoid6 tests: keys match `bed.json`, `UMask` is set, the directory mode is `0750`.
7. [x] zoid6 `INSTALL.md`: document the one-time group step and where the log is.
8. [ ] Commit bed, then zoid6, then bump the meta-repo pointers.

## Operator steps (after deploy and restart)

```
sudo usermod -aG zoid6 opencode     # root, once; opencode must log in again
tail /var/log/zoid6/startup.log     # as opencode
```

## Verification

Not yet verified on minotaur. Run the operator steps, then check the three points below.

- After a restart, `startup.log` holds the startup line.
- `opencode` can read it, and cannot read `journalctl`.
- The file is `0640`, owned by `zoid6:zoid6`.
