#!/usr/bin/env python3
"""Reassign public bootstrap SQL functions to the dedicated ``zoid6`` role.

This is an out-of-band helper for databases that were bootstrapped
outside of ``bbsengine6.backend.stage_zero`` (for example, by running
the ``.sql`` files directly via ``psql`` as the cluster superuser,
which leaves the helpers owned by whichever role happened to be
``current_user`` at install time).

For databases that ARE bootstrapped via ``stage_zero``, this script
is **not needed**: the new ``backend.checkzoid6owner`` module runs
automatically between ``checkfunctions`` and ``checksuperuser`` in
``stage_zero`` and migrates ownership to ``zoid6`` on first run.

The script connects as the role named in ``PGUSER`` because
``ALTER FUNCTION ... OWNER TO`` requires the caller to be either the
current owner or a superuser. The caller chooses that role; the code does not. If the
role ``zoid6`` does not yet exist, the script creates it as
``NOSUPERUSER NOCREATEDB NOCREATEROLE NOLOGIN INHERIT`` first, since
``ALTER FUNCTION ... OWNER TO zoid6`` cannot succeed against a
non-existent role.

Idempotent: re-running on a DB where the functions are already owned
by ``zoid6`` and the role exists is a no-op for both the role
creation and the per-function reassignment.
"""
from __future__ import annotations

import os
import sys

import psycopg


TARGET = "zoid6"
DBNAME = "zoid6"
FUNCTIONS = (
    "manage_database_priv",
    "manage_role_privs",
    "manage_secondary_role",
    "manage_schema_priv",
    "get_role_privs",
)


def superuser() -> str:
    """The superuser to connect as, from PGUSER. Never a built-in name.

    The owner migration needs a role that can ALTER FUNCTION ... OWNER TO, so the
    caller names it. Nothing in the code chooses it.
    """
    user = os.environ.get("PGUSER", "").strip()
    if not user:
        raise SystemExit("set PGUSER to the superuser that owns the bootstrap functions")
    return user


def main() -> int:
    with psycopg.connect(host="localhost", dbname=DBNAME, user=superuser()) as conn:
        with conn.cursor() as cur:
            cur.execute(
                "SELECT 1 FROM pg_roles WHERE rolname = %s",
                (TARGET,),
            )
            if cur.fetchone() is None:
                cur.execute(
                    f"CREATE ROLE {TARGET} "
                    f"NOSUPERUSER NOCREATEDB NOCREATEROLE NOLOGIN INHERIT"
                )
                print(f"  role {TARGET}: created")
            else:
                print(f"  role {TARGET}: already exists")

            for fn in FUNCTIONS:
                cur.execute(
                    """
                    SELECT pg_catalog.pg_get_function_identity_arguments(p.oid)
                    FROM pg_proc p
                    JOIN pg_namespace n ON p.pronamespace = n.oid
                    WHERE n.nspname = 'public' AND p.proname = %s
                    """,
                    (fn,),
                )
                row = cur.fetchone()
                if row is None:
                    print(f"  skip {fn}: not found in public")
                    continue
                args = row[0]
                cur.execute(
                    f"ALTER FUNCTION public.{fn}({args}) OWNER TO {TARGET}"
                )
                print(f"  {fn}({args}) -> {TARGET}")
        conn.commit()
    print("done")
    return 0


if __name__ == "__main__":
    sys.exit(main())
