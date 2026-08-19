#!/usr/bin/env python3
"""Reassign public bootstrap SQL functions to a given target role.

Used by the dev / no-sudo install flow so the opencode user (running
the bed daemon in this venv) can satisfy
``bbsengine6.backend.checkengine.acceptable_owners`` without editing
source. Production DBs are untouched — this script only connects to
the dev DB at ``host=localhost dbname=zoid6``.

The script connects as ``SUPERUSER`` (jam) because ``ALTER FUNCTION
... OWNER TO`` requires the caller to be the current owner or a
superuser. ``jam`` authenticates over TCP via the local
``host 127.0.0.1/32 trust`` rule in pg_hba.conf.

Idempotent: re-running on a DB where the functions are already owned
by ``TARGET`` is a no-op.
"""
from __future__ import annotations

import sys

import psycopg


TARGET = "opencode"
SUPERUSER = "jam"
DBNAME = "zoid6"
FUNCTIONS = (
    "manage_database_priv",
    "manage_role_privs",
    "manage_secondary_role",
    "manage_schema_priv",
    "get_role_privs",
)


def main() -> int:
    with psycopg.connect(host="localhost", dbname=DBNAME, user=SUPERUSER) as conn:
        with conn.cursor() as cur:
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
