# Zoid6 TODO (index)

This file is the index for zoid6's TODO. The detail lives in `handbook/todo/`, one file per
group. Open counts are checkbox items that are not yet done. Items that belong to another
repo are listed in that repo's own TODO and are cross-referenced here, not copied.

## Group files (zoid6)

| File | Topic | Open |
|---|---|---|
| [code-review.md](handbook/todo/code-review.md) | Code Review | 26 |
| [console-scripts-and-version.md](handbook/todo/console-scripts-and-version.md) | Console scripts and _version.py | 25 |
| [error-handling.md](handbook/todo/error-handling.md) | Error Handling | 12 |
| [history.md](handbook/todo/history.md) | History and record sections | 5 |
| [install-build-workflow.md](handbook/todo/install-build-workflow.md) | install: build deploy workflow | 29 |
| [logging.md](handbook/todo/logging.md) | Logging | 12 |
| [packaging-and-systemd.md](handbook/todo/packaging-and-systemd.md) | Build, packaging and systemd | 0 |
| [password-migration.md](handbook/todo/password-migration.md) | Password migration | 0 |
| [rename-register_module-function-to-register.md](handbook/todo/rename-register_module-function-to-register.md) | rename register_module to register — zoid6 | 2 |
| [router-and-config.md](handbook/todo/router-and-config.md) | Router and config | 15 |
| [shared-venv.md](handbook/todo/shared-venv.md) | Shared venv | 8 |
| [standardized-finally-clause-in-main.md](handbook/todo/standardized-finally-clause-in-main.md) | standardized finally clause in main — zoid6 | 2 |
| [table-invites.md](handbook/todo/table-invites.md) | Table invites and join rules | 21 |
| [testing.md](handbook/todo/testing.md) | Testing | 24 |
| [zoid6-tui-prod-split.md](handbook/todo/zoid6-tui-prod-split.md) | zoid6.tui and zoid6.prod deploy split | 2 |

## Session module (split by owner)

| File | Owner | Open |
|---|---|---|
| [session-casino.md](handbook/todo/session-casino.md) | casino (Phase 4) | 25 |
| [session-config-bed.md](handbook/todo/session-config-bed.md) | bed (bed.json session section and config tests) | 6 |
| [session-config-zoid6.md](handbook/todo/session-config-zoid6.md) | zoid6 (zoid6.json session section) | 1 |
| [session-empyre.md](handbook/todo/session-empyre.md) | empyre (Phase 5) | 23 |
| [session-service-bbsengine6.md](handbook/todo/session-service-bbsengine6.md) | bbsengine6 (services/session.py, session.sql, tests) | 47 |

## Owned by other repos (cross-reference)

| Item | Owner | Where it is tracked |
|---|---|---|
| BED bearer token | bed | `bed/TODO.md` "Bearer token" ([zoid6 copy](handbook/todo/bed-bearer-token.md)) |
| Bed network configuration | bed | `bed/TODO.md` ([zoid6 copy](handbook/todo/bed-network-config.md)) |
| Generic invite system and channel access | bbsengine6 | `bbsengine6/TODO.md` ([zoid6 copy](handbook/todo/bbsengine6-invite-and-channels.md)) |
| Bottom bar wire push | bbsengine6 | `bbsengine6/TODO-BOTTOMBAR.md` |
| Table invites and join rules (casino flags) | casino | `casino/TODO.md` ([zoid6 copy](handbook/todo/table-invites.md)) |
| Session service, casino and empyre migration | bbsengine6, casino, empyre | see the session files above |

## History

Completed phases and the 2026-06-28 cross-repo session plan are kept in [history.md](handbook/todo/history.md).

## Totals

- Open checkboxes across zoid6 group and session files: 325
- Original `zoid6/TODO.md` open checkboxes before the split: 321 (verified: every item appears in exactly one group file).
