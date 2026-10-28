# zoid6: installing the daemon end to end

This is the operator guide for running zoid6 on a host. It covers the OS account, the Python
venv, the database role, the configuration, the systemd unit, and how to check it works.

> **Status:** steps marked *(built)* exist and are tested. Steps marked *(pending)* are part of the
> planned install and are not in the code yet.

zoid6 is bed (the WebSocket daemon) running with zoid6's config and router. It runs as the OS user
`zoid6`, and it connects to PostgreSQL as the login role `bed_zoid6`, authenticated by identd (no
password).

## 1. Prerequisites

- PostgreSQL with the `zoid6` database, and the bbsengine6 and bed schemas installed.
- `oidentd` installed and enabled, listening on tcp/113. zoid6's unit requires it.
- Python 3.12, and `sudo` for the operating-system steps.
- The `oidentd` configuration from `zoidlan/minotaur/oidentd/etc-oidentd.conf`. The stock defaults
  are used: spoofing and hiding are denied, and the daemon's connections are answered truthfully.

## 2. The OS account *(built: sysusers)*

The `zoid6` user and group come from `zoid6/src/zoid6/daemon/zoid6.sysusers`:

```
make -C zoid6 install-sysusers
```

This installs `/usr/lib/sysusers.d/zoid6.conf` and runs `systemd-sysusers`, which creates the user
`zoid6` (group `zoid6`) and its home directory `/home/zoid6`. Check it:

```
id zoid6
ls -ld /home/zoid6
```

The account is a system account for the daemon. It does not need a login shell for the daemon's
use.

*(pending: the `casino --install` step will create the account itself as part of the database
install. Until then, use `install-sysusers`.)*

## 3. The venv *(built)*

The daemon runs from a venv owned by `zoid6`, at `/home/zoid6/venv`. There are two ways to create it.

### 3a. From staged wheels (the normal route)

Use this when the wheels are built on a build host and the `zoid6` account has no access to the
source tree. The `zoid6` account never reads `/home/opencode`: the wheels are copied into its home.

**Sync (run as `jam`, with sudo, after each build):**

```
sudo sh /home/opencode/data/work/dist-wheels/sync-zoid6-wheels.sh
```

This copies the wheels named in `dist-wheels/zoid6-wheels.txt` into `/home/zoid6/dist-wheels`, owned
by `zoid6`. It removes any wheel in the target that is no longer listed, so a stale build cannot be
installed, and it copies the install script alongside them. To check first, add `--dry-run`.

Update `zoid6-wheels.txt` when a package is rebuilt, so it names the new file.

**Install (run as the `zoid6` account, no sudo):**

```
sh /home/zoid6/dist-wheels/INSTALL-zoid6-venv.sh
```

This creates `/home/zoid6/venv` if it is missing, installs every wheel in `/home/zoid6/dist-wheels` in
one `pip` call (pip orders the dependencies), and prints each package's version. Repeat the sync and
the install after every build.

### 3b. From the source tree (a host with its own build)

On a host where the `zoid6` account can build from the source tree itself, use the Makefile target:

```
make -C zoid6 install-venv VENV_DIR=/home/zoid6/venv
```

What this does:

- Runs `install-sysusers` first (section 2), so the account exists before the venv is created.
- Creates the venv as `zoid6` (`sudo -u zoid6`), and upgrades pip, build, setuptools and wheel.
- Builds **bed's** wheel and installs it into the venv. zoid6's own code is served by bed through the
  `--router zoid6.api.handler.MessageRouter` argument, so bed's wheel is the one that runs.
- Relabels `bin/` for SELinux where `semanage` and `restorecon` exist.

The target needs `sudo`. Without `VENV_DIR`, it defaults to `/var/lib/zoid6/venv`, which is the old
location; pass `VENV_DIR` to use `/home/zoid6/venv`.

The engine (`bbsengine6`) and casino are installed separately, with `make -C bbsengine6 venv` and
the casino venv target, each with `VENV_DIR=/home/zoid6/venv` and run as `zoid6`.

*(pending: a deploytool target that installs all three packages into the zoid6 venv and verifies
each one.)*

## 4. The database role *(pending: the migration and the `bed_zoid6` role are planned, not yet written)*

The daemon's login is `bed_zoid6`, a `LOGIN NOINHERIT` role that holds the grants the daemon needs.
It is planned to be created by `src/casino/sql/zoid6_role_migration.sql`, which copies the grants
from `bed` and records the role in `engine.pgrole`. That file is not written yet.

Once it exists, the database owner (not the daemon) runs it on each database:

```
psql -h 127.0.0.1 -d zoid6 -v ON_ERROR_STOP=1 -f src/casino/sql/zoid6_role_migration.sql
```

The migration is idempotent. The `bed` login stays in place until verification passes, and is removed
by `zoid6_role_cutover.sql` (not part of a normal install).

Then the database owner adds the identd mapping, so the OS user `zoid6` logs in as `bed_zoid6`:

- In `pg_ident.conf`: `zoidmap  zoid6  bed_zoid6`
- In `pg_hba.conf`, above any broader rule for the database:
  `host  zoid6  bed_zoid6  127.0.0.1/32  ident  map=zoidmap`
- Reload PostgreSQL.

The DSN in zoid6's configuration does **not** name a user. The login is decided by the map.

## 5. Configuration

zoid6 has no required configuration file. With no `--config`, the daemon uses the bundled
configuration, `zoid6/src/zoid6/data/zoid6.json`, which is installed inside the venv. Nothing has to be
copied, and nothing is read from `/etc`.

The keys operators most often change:

| Key | Default | Meaning |
|---|---|---|
| `bind.host` | `localhost` | Address the WebSocket server listens on. |
| `bind.port` | `8765` | Port the WebSocket server listens on. |
| `bed.name` | `zoid6` | The instance name used in logs and secret paths. |
| `bed.max_restarts`, `bed.restart_delay` | `10`, `5` | Restart policy for the in-process supervisor. |
| `services.<name>.enabled` | see below | Turns a service on or off. |
| `casino.clock.max_turn_seconds` | `120` | Sysop ceiling for a table's turn clock. |
| `session.idle_minutes` | `15` | Idle time before a session is dropped. |

Services on by default: `member`, `channel`, `casino`. Off by default: `auth` (bed handles it),
`bank` (bed serves it), `postoffice`, `empyre`, `murdermotel`, `mistermcfeely`.

To override the bundled configuration, copy it, edit the copy, and pass it with `--config`:

```
install -m 0644 zoid6/src/zoid6/data/zoid6.json /home/zoid6/zoid6.json
# edit /home/zoid6/zoid6.json, then add to the unit's ExecStart:
#   --config /home/zoid6/zoid6.json
```

Only do this when a setting must differ from the bundled file. Otherwise the bundled file is the one
to trust, since it is the one tested with the code.

## 6. The systemd unit *(pending: the source still has `EnvironmentFile` and no oidentd dependency)*

The unit source is `bed/src/bed/daemon/zoid6.service`. Once the planned changes land, it runs as
`zoid6`, uses the venv at `/home/zoid6/venv`, and requires `oidentd`. Install it:

```
sudo install -m 0644 zoid6-unit.service /usr/lib/systemd/system/zoid6.service
sudo systemctl daemon-reload
sudo systemctl enable --now zoid6
```

*(pending: the unit's `ExecStart` path is rendered from `@VENV_DIR@` by the install target, the same way
`bed.service` is. Until then, check the path in the installed unit matches your venv.)*

## 7. Verify

```
systemctl status zoid6
journalctl -u zoid6 --since "5 minutes ago" | grep -i "MessageRouter\|error\|permission"
```

- The journal should show `Registered router: MessageRouter` and no `MessageRouter not found` line.
- The daemon's database login must be `bed_zoid6`. A `permission denied` error names the role it ran as.
- A WebSocket probe: authenticate, then send `list_tables` with `game_type: "poker"`. The reply should
  be `table_list`, not `no_handler`.

## 8. Troubleshooting

| Symptom | Likely cause | Check |
|---|---|---|
| Daemon does not start; `identd` errors | `oidentd` not running | `systemctl status oidentd` |
| `permission denied for schema casino` or `…__table` | Daemon connected as another role | The `pg_ident` map and `pg_hba` line (section 4) |
| `no_handler` for `poker_*` or `list_tables` | casino's router did not load | The journal for `MessageRouter not found` |
| `relation … does not exist` | A migration has not been run | Run the migration in section 4 |
| Connection refused on 8765 | Wrong `bind.port`, or the daemon is down | `systemctl status zoid6`, `ss -ltnp` |

## See also

- `SMOOTHSTATE.md` in this directory.
- `casino/handbook/POKER_PERSISTENCE.md`, for the poker and table persistence rules.
- `zoidlan/minotaur/oidentd/`, for the identd configuration.
