"""The ``zoid6`` sysop command: ``--install`` and ``--check-acl`` (phase 10).

Runs as the zoid6 account (no sudo). The root-only steps are not here: they are
printed as the command to run as root, and deploytool's zoid6.prod will own them.

  zoid6 --install [--prefix PATH]   apply the ACL plan, then install the staged wheels
  zoid6 --check-acl [--prefix PATH] report the ACL state; change nothing

``--prefix`` sets the venv root (default /home/zoid6/venv). The venv's dist-wheels
directory is the directory the wheels are staged into, /home/zoid6/dist-wheels by
default; the installer reads it from its own directory.
"""

from __future__ import annotations

import argparse
import os
import pwd
import subprocess
import sys
from collections.abc import Sequence
from pathlib import Path

from bbsengine6 import acl

ACCOUNT = "zoid6"
DEFAULT_VENV = "/home/zoid6/venv"
DEFAULT_DIST = "/home/zoid6/dist-wheels"
INSTALLER_NAME = "INSTALL-zoid6-venv.sh"


class SysopError(RuntimeError):
    """The command refused, or a step failed. The message says what to do."""


def effective_account() -> str:
    """The account name for the effective UID. Not $USER, which can be set by anyone."""
    return pwd.getpwuid(os.geteuid()).pw_name


def require_zoid6() -> None:
    who = effective_account()
    if who != ACCOUNT:
        raise SysopError(
            f"zoid6: must run as the {ACCOUNT} account (this is {who}). "
            f"Run it as {ACCOUNT}; sudo is not used."
        )


def _paths(prefix: str | None) -> tuple[str, str]:
    venv = prefix or DEFAULT_VENV
    if prefix:
        dist = str(Path(prefix).parent / "dist-wheels")
    else:
        dist = DEFAULT_DIST
    return venv, dist


def check_acl(prefix: str | None = None) -> list[tuple[acl.AclEntry, bool]]:
    """Read-only. Each ACL entry with whether its effective permission is present."""
    venv, dist = _paths(prefix)
    results = []
    for entry in acl.acl_plan(venv=venv, dist_wheels=dist):
        ok = acl.check_acl(entry.path, entry.group, entry.perms)
        results.append((entry, ok))
    return results


def apply_acl(prefix: str | None = None) -> None:
    """Grant every ACL in the plan. Raises SysopError for a missing path or a setfacl failure."""
    venv, dist = _paths(prefix)
    plan = acl.acl_plan(venv=venv, dist_wheels=dist)
    # Check every path before the first setfacl: a refusal must change nothing.
    missing = sorted({e.path for e in plan if not os.path.exists(e.path)})
    if missing:
        raise SysopError(
            f"zoid6 --install: {', '.join(missing)} does not exist. Create it first, as "
            f"{ACCOUNT} (or root), then re-run. No ACL was changed."
        )
    for entry in plan:
        try:
            acl.grant_acl(entry.path, entry.group, entry.perms, default=entry.default)
        except (RuntimeError, FileNotFoundError) as exc:
            raise SysopError(f"zoid6 --install: {exc}") from exc


def install_wheels(prefix: str | None = None) -> None:
    """Run the staged installer with DIST and VENV set from the prefix."""
    venv, dist = _paths(prefix)
    installer = os.path.join(dist, INSTALLER_NAME)
    if not os.path.isfile(installer):
        raise SysopError(
            f"zoid6 --install: {installer} is not staged. Run deploy zoid6.wheels first."
        )
    env = {**os.environ, "DIST": dist, "VENV": venv}
    proc = subprocess.run(["sh", installer], env=env, check=False)
    if proc.returncode != 0:
        raise SysopError(f"zoid6 --install: the installer failed (rc={proc.returncode})")


def _print_check(results: list[tuple[acl.AclEntry, bool]]) -> bool:
    all_ok = True
    for entry, ok in results:
        mark = "ok  " if ok else "MISS"
        scope = " (default)" if entry.default else ""
        print(f"{mark} {entry.group}:{entry.perms:<4} {entry.path}{scope}")
        all_ok = all_ok and ok
    return all_ok


def _parser() -> argparse.ArgumentParser:
    p = argparse.ArgumentParser(prog="zoid6", description="zoid6 account administration.")
    mode = p.add_mutually_exclusive_group(required=True)
    mode.add_argument("--install", action="store_true",
                      help="apply the ACL plan, then install the staged wheels")
    mode.add_argument("--check-acl", action="store_true",
                      help="report the ACL state read-only")
    p.add_argument("--prefix", default=None,
                   help=f"venv root (default {DEFAULT_VENV})")
    return p


def main(argv: Sequence[str] | None = None) -> int:
    args = _parser().parse_args(argv)
    try:
        require_zoid6()
        if args.check_acl:
            ok = _print_check(check_acl(args.prefix))
            if not ok:
                print("zoid6 --check-acl: one or more grants are missing. Run zoid6 --install.")
                return 1
            print("zoid6 --check-acl: all grants present.")
            return 0
        # --install
        apply_acl(args.prefix)
        print("zoid6 --install: ACL plan applied.")
        install_wheels(args.prefix)
        print("zoid6 --install: wheels installed.")
        ok = _print_check(check_acl(args.prefix))
        if not ok:
            print("zoid6 --install: the ACL check still reports a gap after applying the plan.")
            return 1
        print("zoid6 --install: complete.")
        return 0
    except SysopError as exc:
        print(str(exc), file=sys.stderr)
        return 1
