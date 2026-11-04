# zoid6 TODO: Build, packaging and systemd

Owner: zoid6
Open: 0   (prose section; no checkboxes)

Split from `zoid6/TODO.md` (the index). Items are unchanged from that file.

---


Committed in `7a30ad5` (`zoid6: dynamic version, release Makefile
targets, bed dependency`). bed-side changes are recorded in
`bed/TODO.md` under "## Systemd deployment" and "## CLI `--config`
flag"; do not duplicate them here.

### zoid6 changes in this commit
- `pyproject.toml`: `version` is now `dynamic`, sourced from
  `zoid6._version.__version__`. `requires-python` aligned to
  `>=3.9,<3.13` per `specs/AGENTS.md` (mediapipe constraint). New
  runtime dep `bed>=0.0.1.dev2026` so the unified router can rely
  on the `bed` console script being installed alongside zoid6.
- `_version.py`: holds `__version__` (default `"0.0.0"`),
  `githash`, `datestamp`. Stamped by `make version`.
- `__init__.py`: re-exports `__version__` from `_version` so
  `zoid6.__version__` keeps working and the existing
  `tests/test_message_router.py::TestPackageMetadata::test_version_is_defined`
  still passes.
- `Makefile`: added `version`, `build`, `rename-sdist`, `sign`,
  `release` targets mirroring `empyre/src/Makefile`; preserved
  the existing dev/test targets.

### Follow-up: zoid6-bed.service
The systemd unit for zoid6-as-deployed-by-bed is **not** in this
repo. It will be added in a follow-up commit. The expected shape:
```
[Unit]
Description=zoid6 BBS Engine Daemon
After=network.target postgresql.service
Wants=postgresql.service

[Service]
Type=simple
User=bed
Group=bed
WorkingDirectory=/opt/bed
EnvironmentFile=-/etc/bed/bed.env

ExecStart=/usr/bin/bed \
  --config /opt/zoid6/src/zoid6/data/zoid6.json \
  --router zoid6.api.handler.MessageRouter \
  --no-autorestart

ExecReload=/bin/kill -HUP $MAINPID
Restart=on-failure
RestartSec=5s
KillMode=mixed
KillSignal=SIGTERM
TimeoutStopSec=30s
StandardOutput=journal
StandardError=journal
SyslogIdentifier=zoid6-bed

NoNewPrivileges=true
PrivateTmp=true
ProtectSystem=strict
ProtectHome=yes
ReadWritePaths=/var/log/zoid6

[Install]
WantedBy=multi-user.target
```
Differences vs. the generic `bed.service` (in `bed/src/bed/daemon/bed.service`):
- `--config` explicitly points at the zoid6 unified-router config
  instead of using the packaged default.
- `--router zoid6.api.handler.MessageRouter` to load enabled zoid6
  module routers.
- `SyslogIdentifier=zoid6-bed` so `journalctl -u zoid6-bed` filters
  cleanly.
- `ReadWritePaths=/var/log/zoid6` (zoid6 has its own log dir).

This unit will be installed via a `make install-systemd` target
mirroring the one in `bed/src/Makefile` (i.e. copy
`zoid6/src/zoid6/daemon/zoid6-bed.service` to
`/etc/systemd/system/zoid6-bed.service` and `daemon-reload`).

### Prerequisite (added 2026-07-22)
The FHS plumbing for `/etc/zoid6/zoid6.json` and the tmpfiles
drop-in has landed first, so the draft unit above can resolve
its `--config` path:
- `zoid6/src/zoid6/daemon/zoid6.tmpfiles` (in-package, shipped
  in the wheel via `package-data`).
- `zoid6/src/zoid6/data/zoid6.json` is the canonical source;
  `make -C zoid6/src install-etc` rsyncs it from
  `usr/share/factory/etc/zoid6/` to `/etc/zoid6/zoid6.json`.
- `make -C zoid6/src install-tmpfiles` installs the tmpfiles
  drop-in to `/usr/lib/tmpfiles.d/zoid6.conf` and materializes
  `/etc/zoid6`.
- The draft unit above should reference `/etc/zoid6/zoid6.json`
  instead of `/opt/zoid6/src/zoid6/data/zoid6.json` once it ships.

### Ownership on `/etc/zoid6/` (added 2026-07-22)
All zoid6 FHS files are owned `root:root`:
- `/etc/zoid6/` → `755 root:root`
- `/etc/zoid6/zoid6.json` → `644 root:root`
- `/usr/lib/tmpfiles.d/zoid6.conf` → `644 root:root`

The `install-etc` and `install-tmpfiles` Makefile targets use
`sudo install -m 0644 -o root -g root …` (not `rsync`) so
ownership is set explicitly rather than inherited from the
source file. Both targets also run a follow-up
`sudo chown root:root …` as a self-heal, so re-running
`make -C src install-fhs` from a real-privilege shell fixes
ownership regardless of how a prior install landed it.

**Dev-box gotcha**: in containers with `NoNewPrivs` set
(e.g. this dev environment), `sudo install` runs as the
original user, so the files end up `opencode:opencode` on
first install. The follow-up `sudo chown` is *also* blocked
in that case. The directory `/etc/zoid6/` is created by
`systemd-tmpfiles` which honors the tmpfiles file's owner
column, so it lands `root:root` even in the no-privilege
container. The two files are fixed by re-running
`make -C src install-fhs` from a real-privilege shell — the
chown steps in the Makefile then execute for real.

### Follow-up: bed CLI in this repo's CI
Any CI for zoid6 that runs `make test` should also
`pip install -e ../bed/src` first (or whatever layout the deploy
uses) so the new `bed>=0.0.1.dev2026` dep resolves. The current
test invocation in this repo's `Makefile` (`pytest zoid6/tests`)
only imports zoid6, so the import-time check passes; the runtime
declaration is enough to keep pip happy during install.

---
