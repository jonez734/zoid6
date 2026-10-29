# zoid6: installing the daemon end to end

This is the operator guide for running zoid6 on a host. It covers the OS account, the Python
venv, the database role, the configuration, the systemd unit, and how to check it works.

> **Status:** every step is built and was run on minotaur. Steps that need root or the `zoid6`
> account say who runs them. Two items remain planned: the `casino --install` account step, and a
> single deploytool target for the venv (both marked *(pending)* below).

zoid6 is bed (the WebSocket daemon) running with zoid6's config and router. It runs as the OS user
`zoid6`, and it connects to PostgreSQL as the login role `bed_zoid6`, authenticated by identd (no
password).

## 1. Prerequisites

- PostgreSQL with the `zoid6` database, and the bbsengine6 and bed schemas installed.
- `oidentd` installed and enabled, listening on tcp/113. zoid6's unit requires it.
- Python 3.12, and `sudo` for the operating-system steps.
- The `oidentd` configuration from `zoidlan/minotaur/oidentd/etc-oidentd.conf`. The stock defaults
  are used: spoofing and hiding are denied, and the daemon's connections are answered truthfully.

## 2. The OS account *(built)*

The daemon runs as the Unix account `zoid6`. Create it once per host, as root.

**Create the account with `useradd`:**

```
sudo useradd --system --create-home --home-dir /home/zoid6 --user-group \
  --comment "zoid6 BBS Router" --shell /bin/bash zoid6
sudo chmod 0750 /home/zoid6
```

- `--system` gives a system UID; the account needs no password, since it never logs in. The daemon
  connects to PostgreSQL as `bed_zoid6` through identd (section 4a), not as this account.
- `--create-home --home-dir /home/zoid6` creates the home the venv and the staged wheels live in.
- `--user-group` makes the group `zoid6`, and `chmod 0750` keeps the home private to the account.
- `--shell /bin/bash` is what the account has on minotaur. Its shell is used only by the install
  commands below, which run it with `sudo -u zoid6`.

`useradd` fails if the account already exists. Check first:

```
id zoid6 && ls -ld /home/zoid6
```

**Or create it from the repo's sysusers file** (this also records the account for `systemd-sysusers`):

```
sudo make -C /home/opencode/data/work/zoid6/src install-sysusers
```

This installs `/usr/lib/sysusers.d/zoid6.conf` and runs `systemd-sysusers`. It creates the same
account with no login shell, so use `useradd` above if you need the shell for the install commands.

Confirm the account:

```
id zoid6
getent passwd zoid6
ls -ld /home/zoid6
```

*(pending: `casino --install` will create the account itself as part of the database install.)*

## 3. The venv *(built)*

The daemon runs from a venv owned by the `zoid6` account, at `/home/zoid6/venv`. Create it after
section 2.

**Create the venv (the commands to run, chained with `&&`):**

```
cd /home/opencode/data/work/zoid6 && sudo make sync-wheels && sudo -u zoid6 sh /home/zoid6/dist-wheels/INSTALL-zoid6-venv.sh && sudo systemctl restart zoid6 && journalctl -u zoid6 --since "2 minutes ago" | tail -5
```

- `sudo make sync-wheels` (root) copies the wheels named in `dist-wheels/zoid6-wheels.txt` into
  `/home/zoid6/dist-wheels`, owned by `zoid6`.
- `sudo -u zoid6 sh /home/zoid6/dist-wheels/INSTALL-zoid6-venv.sh` (the account) creates `/home/zoid6/venv` with
  `/usr/bin/python3.12` and installs those wheels. It needs no root, because the account owns its home.
- The restart and journal check confirm the daemon starts on the new venv.

The two routes below explain how the wheels get there. Use 3a for the normal route.

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

**Then relabel the venv for SELinux (run as `jam`, with sudo).** On an enforcing host the service
cannot execute a binary whose `bin/` is not labelled `bin_t`; the failure shows as `status=203/EXEC`
with "Permission denied". This step is needed after every install, because the staged route does not
label the venv:

```
sudo make -C /home/opencode/data/work/zoid6/src restorecon VENV_DIR=/home/zoid6/venv
```

**The one-command route (deploy, then restart).** This is the normal install. Run these as `jam`,
from the meta-repo at `/home/opencode/data/work`.

1. Install deploytool (once per change to deploytool):

   ```
   cd /home/opencode/data/work/deploytool && make deploy-tui PYTHON=python3.12 PIP="python3.12 -m pip"
   ```

2. One-time, per host: make the account's directories writable by the `zoid6` group, which jam is in.
   Run it as root or as `zoid6`:

   ```
   sudo -u zoid6 mkdir -p /home/zoid6/dist-wheels && sudo chmod -R g+rwX /home/zoid6/dist-wheels /home/zoid6/venv
   ```

3. Deploy the venv:

   ```
   deploy zoid6.venv --with-deps
   ```

   This builds the five wheels from git HEAD (`zoid6/scripts/build_zoid6_wheels.sh`, which deploy runs
   and labels `helper:`), stages them and the installer into `/home/zoid6/dist-wheels`, and installs them
   into `/home/zoid6/venv` as the `zoid6` account's installer. It ends with `deploy complete`. A step that
   stops prints the one command to run; fix it, then run step 3 again.

4. Restart the daemon:

   ```
   sudo systemctl restart zoid6
   systemctl is-active zoid6
   journalctl -u zoid6 --since "5 minutes ago" | grep -E "startup completed|PingService"
   ```

   Expect `active`, a `startup completed, <n> modules enabled` line, and the new bed version in
   `PingService`.

The manual sync and install commands above remain the way to install by hand. The one-command route
does the same steps.

**The startup log (for `opencode` to read).** The daemon writes its startup lines to
`/var/log/zoid6/startup.log` as well as the journal. The file is `0640` and group `zoid6`, and it rotates by
size. Its path and rotation are set in `bed.json` (`startup_log`) and must match the same section in
`zoid6.json` (a test enforces this). An empty `path` turns the file off.

One-time, as root, so the `opencode` account can read the file through group `zoid6`:

```
sudo usermod -aG zoid6 opencode
```

`opencode` must log in again before the group takes effect. After a restart, read the startup lines as
`opencode`:

```
tail /var/log/zoid6/startup.log
```

The file holds only the startup lines (`startup completed` and `startup_failed`), not request traffic.
If `/var/log/zoid6` is not writable, the daemon still starts and logs a warning; the startup lines then go
to the journal only.

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

The target needs `sudo`. `VENV_DIR` defaults to `/home/zoid6/venv`, the account's own venv that the
unit runs; pass `VENV_DIR` only to use another location.

The engine (`bbsengine6`) and casino are installed separately, with `make -C bbsengine6 venv` and
the casino venv target, each with `VENV_DIR=/home/zoid6/venv` and run as `zoid6`.

*(pending: a deploytool target that installs all three packages into the zoid6 venv and verifies
each one.)*

## 4. The database role *(built: `zoid6_role_migration.sql`; run by the schema owner)*

The daemon's login is `bed_zoid6`, a `LOGIN NOINHERIT` role that holds the grants the daemon needs.
`zoid6` itself stays a non-login schema owner: do not grant it login. The migration
`src/casino/sql/zoid6_role_migration.sql` creates `bed_zoid6`, copies every privilege `bed` holds on
the `engine` and `casino` schemas (read from the catalog ACLs), and records the role in `engine.pgrole`.
`bed` is left in place for rollback.

Run it as the schema owner (or a `zoid6` member), on each database:

```
psql -h 127.0.0.1 -d zoid6 -v ON_ERROR_STOP=1 -f src/casino/sql/zoid6_role_migration.sql
```

It is idempotent. The daemon connects as `bed_zoid6` because the unit passes `--databaseuser bed_zoid6`
(section 6). Without that flag the DSN names no user, and libpq logs in as the OS user `zoid6`, which is
not a login.

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

## 4a. The identd mapping on the server *(built: `install-pgsql`; run by jam)*

The daemon logs in as `bed_zoid6` over TCP, and PostgreSQL authenticates it with identd. The OS user
`zoid6` must map to `bed_zoid6`, and `pg_hba.conf` must allow that login from this host. Two files on
the server carry this, and they live under `/var/lib/pgsql/18/data/`, owned by `postgres`:

- `pg_ident.conf` gets the map `zoidmap  zoid6  bed_zoid6`.
- `pg_hba.conf` gets `host zoid6 bed_zoid6 127.0.0.1/32 ident map=zoidmap`, placed above the first
  existing rule for database `zoid6`, because the first matching rule wins.

The managed copies are in `handbook/pgsql/` (`pg_ident.zoid6.conf`, `pg_hba.zoid6.conf`). The
`bed` rollback login has its own copies in `bed/src/bed/daemon/pgsql/`, with the map `bedmap`.

**Run once per server, as root:**

```
sudo make -C /home/opencode/data/work/zoid6/src install-pgsql
```

The target backs up both files (suffix `.bak-<timestamp>`), adds only the lines that are not already
present, validates the server config, and reloads `postgresql-18`. Run it again and it reports
"already in" for each line, so it is safe to repeat.

It is not part of `zoid6.prod`. Deploytool has the same step as `deploytool zoid6.pgsql`, which runs
this target, and `deploytool bed.pgsql` for the rollback copy.

**To back out:** restore the `.bak-<timestamp>` files, then `systemctl reload postgresql-18`. Or delete
the managed lines (the ones shown above) and reload.

**Verify:** the daemon's journal stops showing `role "zoid6" is not permitted to log in`, and
`zoid6.service` stays active.

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

## 6. The systemd unit *(built; the source is `zoid6/src/zoid6/daemon/zoid6.service`)*

The unit runs as `zoid6` from `/home/zoid6`, uses the venv at `/home/zoid6/venv`, requires `oidentd`,
and reads no `/etc` file. Its `ExecStart` passes three arguments that matter:

- `--databasename zoid6` and `--databaseuser bed_zoid6`: the login (section 4).
- `--config <venv>/lib64/python3.12/site-packages/zoid6/data/zoid6.json`: the packaged zoid6
  configuration. zoid6's router requires the `services` block that only this file carries. Without
  `--config`, bed loads its own `bed.json`, and the daemon refuses to start with
  "Member service not registered".
- `--router zoid6.api.handler.MessageRouter`.

**Where the unit is installed.** The installed unit is the vendor copy at
`/usr/lib/systemd/system/zoid6.service`, written by `install-systemd`. zoid6 does not copy the unit
or any `.env` file into `/etc/systemd/system/`. A file there overrides the vendor unit, which is how
a stale symlink shadowed the rendered unit earlier, and every `/etc` write breaks a read-only root.
zoid6 has no `.env` file: none was ever tracked, and the user-mode unit's `EnvironmentFile` is for
developer installs only. To change the unit, change it in `zoid6/src/zoid6/daemon/`, and let
`install-systemd` render it.

`ProtectHome=read-only` lets the service read its venv under `/home/zoid6`. `ReadWritePaths` covers
`/var/log/zoid6` and `/home/zoid6`. `install-systemd` renders `@VENV_DIR@` into `/usr/lib/systemd/system/zoid6.service`. Install it:

```
sudo install -m 0644 zoid6-unit.service /usr/lib/systemd/system/zoid6.service
sudo systemctl daemon-reload
sudo systemctl enable --now zoid6
```

After any change to the unit, run the install again and then `systemctl daemon-reload`. The
installed `ExecStart` should show `/home/zoid6/venv/bin/bed` with both `--databaseuser bed_zoid6` and
`--config`.

## 7. Verify

```
systemctl show zoid6 -p ActiveState -p User -p ExecStart
ps -o user=,args= -C bed
journalctl -u zoid6 --since "5 minutes ago" | grep -i "MessageRouter\|fatal\|error\|permitted"
```

- `ActiveState=active`, `User=zoid6`, and the process runs with `--databaseuser bed_zoid6`.
- The journal shows the router registering its services, and no `MessageRouter not found`,
  `Member service not registered`, or `is not permitted to log in`.
- Database sessions as the daemon login: `select count(*) from pg_stat_activity where usename = 'bed_zoid6'`
  is non-zero while the daemon runs.
- A WebSocket check: authenticate as a member, then send `list_tables` with `game_type: "poker"`.
  The reply is `table_list`.
- Channels: a seated member subscribes to `casino:table:<table>:chat` and gets `subscribed`; a
  non-seated member gets `forbidden`.

## 8. Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| `status=203/EXEC`, "Permission denied" on the venv | SELinux: the venv `bin/` is not labelled `bin_t` | `sudo make -C zoid6/src restorecon VENV_DIR=/home/zoid6/venv` |
| `status=203/EXEC`, "No such file" | `@VENV_DIR@` unrendered, or a stale link shadows the rendered unit | Re-run `install-systemd`; if `systemctl cat zoid6` shows a stale path, remove the stale link by hand (see Stale unit link) |
| `Unit configuration has fatal error ... VENV_DIR@` | The raw template is in use, through a stale link | Remove the stale link by hand (see Stale unit link), then `install-systemd` |
| `Member service not registered` | The daemon loaded bed's `bed.json`, not zoid6's `services` | `ExecStart` must pass `--config` (section 6) |
| `role "zoid6" is not permitted to log in` | The DSN names no user, so libpq logs in as the OS user | `ExecStart` must pass `--databaseuser bed_zoid6`; identd map and `pg_hba` in place (section 4a) |
| `module 'bbsengine6.message' has no attribute 'configure'` | The venv has an older bbsengine6 | Sync and install the current wheels (section 3a) |
| `no_handler`, or `not_authenticated` on every subscribe | The channel router did not receive bed's session registry, or the loader fell back | Install the current bbsengine6 wheel |
| `forbidden: Subscribe authority denied` for a seated poker player | The authority read the cash-table seat table for a poker table | Install the current casino wheel |
| `can't be used in 'await' expression` | Async query path on psycopg-pool 3.3 | Install the current bbsengine6 wheel |
| `permission denied for schema casino` or `…__table` | The daemon connected as another role | The unit's `--databaseuser`, and the grants in section 4 |
| Connection refused on 8765 | Wrong `bind.port`, or the daemon is down | `systemctl status zoid6`, `ss -ltnp` |
| Unit stops after 10 restarts | systemd's start limit | `sudo systemctl reset-failed zoid6` after the fix |

### Stale unit link

A link at `/etc/systemd/system/zoid6.service` shadows the rendered unit in `/usr/lib/systemd/system/`.
The install does not write under `/etc`, so remove a stale link by hand, as root, then reinstall the
unit. Check first:

```
systemctl cat zoid6 | head -3
```

If the first line names `/etc/systemd/system/zoid6.service` and that file is a symlink to the raw
template (its `ExecStart` still contains `@VENV_DIR@`), remove it:

```
sudo rm /etc/systemd/system/zoid6.service && sudo systemctl daemon-reload && make -C bed/src install-zoid6-systemd
```

## Developer route: user-mode unit

For a developer workstation with no system install, zoid6 runs as the user's own service from
`~/.local/zoid6`. This is separate from the minotaur install above and is not used on the host.

```
make -C zoid6/src install-user
make -C zoid6/src install-systemd-user
systemctl --user enable --now zoid6
```

The unit is `zoid6/src/zoid6/daemon/user/zoid6.service`, installed as `~/.config/systemd/user/zoid6.service`. It reads `~/.local/zoid6/etc/zoid6.env`
(the user's home, not `/etc`), and logs to the user journal (`journalctl --user -u zoid6`). Nothing
in this route writes under `/etc` or `/var`.

## See also

- `SMOOTHSTATE.md` in this directory.
- `casino/handbook/POKER_PERSISTENCE.md`, for the poker and table persistence rules.
- `zoidlan/minotaur/oidentd/`, for the identd configuration.
