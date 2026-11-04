# zoid6 TODO: Shared venv

Owner: zoid6 and deploytool
Open: 8   Done: 41

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---


zoid6 is the anchor package for a single shared venv at `/var/lib/zoid6/venv`, owned by a `zoid6` system user/group. All bbsengine6 services (bed, casino, empyre, murdermotel, etc.) are installed into it so any service user can `import` any package. `deploytool` owns the topology — per-project Makefiles do not hard-code venv paths.

### Foundation (zoid6 repo)

- [x] Create `src/zoid6/daemon/zoid6.sysusers`:
  - [x] `u zoid6 - "zoid6 BBS Router" /var/lib/zoid6 -`
  - [x] `g zoid6 - -`
  - [x] `m bed zoid6` (bridge: bed service user joins the zoid6 group)
- [x] Replace `src/zoid6/daemon/zoid6.tmpfiles` with full layout:
  - [x] `d /etc/zoid6 0755 root root - -`
  - [x] `d /var/lib/zoid6 0755 zoid6 zoid6 - -`
  - [x] `d /var/lib/zoid6/venv 0755 zoid6 zoid6 - -`
  - [x] `d /var/log/zoid6 0755 zoid6 zoid6 - -`
- [x] Create `src/zoid6/daemon/zoid6.service` (templated, `@VENV_DIR@` placeholder):
  - [x] `User=zoid6`, `Group=zoid6`, `WorkingDirectory=/var/lib/zoid6`
  - [x] `ExecStart=@VENV_DIR@/bin/bed --config /etc/zoid6/zoid6.json --router zoid6.api.handler.MessageRouter` (`--router` added so zoid6 actually loads its unified router; bed CLI has no `--no-autorestart`)
  - [x] `SyslogIdentifier=zoid6`
- [x] Create `usr/share/factory/etc/zoid6/zoid6.json` (already existed — zoid6 router config; synced via `factory-sync`)
- [x] Update `src/Makefile`:
  - [x] Add `VENV_DIR ?= /var/lib/zoid6/venv`, `VENV_USER ?= zoid6`, `VENV_GROUP ?= zoid6`
  - [x] Add `install-sysusers` / `uninstall-sysusers` (install to `/usr/lib/sysusers.d/zoid6.conf`, `systemd-sysusers`)
  - [x] Add `install-venv` (idempotent: create venv if missing, `pip install --upgrade pip build setuptools wheel`; no wheel install — anchor only; depends on `install-sysusers`)
  - [x] Add `uninstall-venv` (`sudo rm -rf $(VENV_DIR)`)
  - [x] Add `install-systemd` / `uninstall-systemd` (`sed 's|@VENV_DIR@|$(VENV_DIR)|g'` at install time)
  - [x] Add `install-etc` / `uninstall-etc` for `/etc/zoid6/zoid6.json` from factory defaults
  - [x] Add `restorecon` (SELinux `bin_t` fcontext for `$(VENV_DIR)/bin(/.*)?`)
  - [x] Extend `install-fhs` chain: `install-sysusers install-tmpfiles install-venv install-systemd install-etc` (named `install-fhs` to avoid clobbering the existing editable `install` target used by `deploy-tui`)
  - [x] Add `uninstall-fhs` chain
  - [x] Extend `factory-sync` to also sync `zoid6.sysusers`
  - [x] Add `install-tmpfiles: install-sysusers` dependency (dirs owned by `zoid6:zoid6`)
- [x] Update root `Makefile`:
  - [x] Add `install-venv` / `uninstall-venv` / `install-fhs` / `uninstall-fhs` shims delegating to `src/Makefile` (the root `deploy` target is the PHP www deploy and was left untouched)

### deploytool (venv orchestration)

- [x] Add `VENV_LAYOUT` dict to `src/deploytool/lib.py` mapping each project → venv path (registry only; deploytool does not plumb venv vars into `make` — per-project Makefile defaults and `pip` handle venv targeting):
  - [x] Shared `/var/lib/zoid6/venv`: `zoid6`, `bed`, `bbsengine6`, `teos`, `murdermotel`, `empyre`, `casino`, `achilles`, `letteredolive`, `zoidoffice`
  - [x] `atlas` → `/var/lib/atlas/venv` (standalone placeholder)
  - [x] `backuptools`, `deploytool` → `VENV_USER` — the user's active venv (these tools are not part of zoid6; `getdate_next` and `asimov` also default to `VENV_USER`)
  - [x] Any unlisted project → `VENV_USER` (default)
- [x] Add `get_venv_layout(base)` helper; `VENV_USER` sentinel resolves at runtime to the active venv (`VIRTUAL_ENV`, else `sys.prefix` when it differs from `sys.base_prefix`); error if none active
- [x] No `--venv-dir`/`--venv-user`/`--venv-group` CLI flags and no pre-deploy venv-ensure step (dropped: `pip` targets the active venv automatically)
- [x] Add `install-venv` / `uninstall-venv` to the `backuptools` and `deploytool` Makefiles (consistency with the zoid6 anchor pattern; default `VENV_DIR` = active venv)

### bed

- [x] Makefile: `VENV_DIR ?= /var/lib/zoid6/venv`, add `VENV_OWNER ?= zoid6`, `VENV_GROUP ?= zoid6`
- [x] Makefile: replace `sudo -u bed` with `sudo -u $(VENV_OWNER)`
- [x] Template `src/bed/daemon/bed.service`: `ExecStart=@VENV_DIR@/bin/bed --config /etc/bed/bed.json`
- [x] Makefile `install-systemd`: `sed 's|@VENV_DIR@|$(VENV_DIR)|g'`
- [x] Update `bed/FHS.md` with "Shared venv under `zoid6`" section

### Migration (deploy host, post-commit)

- [ ] Stop `bed`
- [ ] Create the shared venv: `make install-sysusers install-tmpfiles install-venv` in `zoid6/src`
- [ ] Run `deploytool bed zoid6` to populate the shared venv
- [ ] Install templated units and `systemctl daemon-reload`
- [ ] Start `bed`, `zoid6`
- [ ] Verify: `sudo -u bed /var/lib/zoid6/venv/bin/python -c "import bbsengine6"` (the original goal)
- [ ] Remove old per-service venvs: `rm -rf /var/lib/bed/venv`
- [ ] Spot-check `bed.json` vs `zoid6.json` for port/bind conflicts (both run `bed` from the shared venv)
