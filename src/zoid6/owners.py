"""zoid6's owner list and the statements that apply it (phase 9).

``sql/owners.json`` lists which role owns each object a package's install creates,
and the table privileges a role needs on another package's table. This module turns
the list into SQL and never runs it itself: ``owner_statements`` returns the
statements and ``apply`` runs them in one transaction.

Only entries for enabled packages produce statements. A disabled package's objects
are never touched, which matches the package gate.

Identifiers are validated before they reach SQL. Names must match the same pattern as
bbsengine6's manifests, and a privilege must be on a fixed list, so no value from the
file is ever interpolated unchecked.
"""

from __future__ import annotations

import json
import re
from dataclasses import dataclass, field
from importlib import resources
from typing import Any

SCHEMA_VERSION = 1
OWNERS_FILE = "owners.json"

_IDENT = r"[a-z_][a-z0-9_]*"
_NAME_RE = re.compile(rf"^{_IDENT}(\.{_IDENT})?$")
_BARE_RE = re.compile(rf"^{_IDENT}$")
_ARGS_RE = re.compile(r"^[A-Za-z0-9_\[\], ]*$")
KINDS = frozenset({"table", "view", "sequence", "function"})
PRIVILEGES = frozenset({"SELECT", "INSERT", "UPDATE", "DELETE", "EXECUTE", "USAGE"})


class OwnersError(ValueError):
    """Raised when owners.json does not validate."""


@dataclass(frozen=True)
class OwnerEntry:
    package: str
    object: str
    kind: str
    owner: str
    args: str | None = None


@dataclass(frozen=True)
class GrantEntry:
    package: str
    object: str
    kind: str
    privileges: tuple[str, ...]
    to: str


@dataclass(frozen=True)
class OwnerList:
    entries: tuple[OwnerEntry, ...]
    grants: tuple[GrantEntry, ...] = field(default_factory=tuple)


def _name(where: str, value: Any, kind: str) -> str:
    if not isinstance(value, str):
        raise OwnersError(f"{where} must be a string")
    pattern = _BARE_RE if kind == "role" else _NAME_RE
    if not pattern.match(value):
        raise OwnersError(f"{where} {value!r} is not a valid identifier")
    return value


def _check_owner(index: int, e: Any) -> OwnerEntry:
    where = f"entries[{index}]"
    if not isinstance(e, dict):
        raise OwnersError(f"{where} must be an object")
    package = e.get("package")
    if not isinstance(package, str) or not _BARE_RE.match(package):
        raise OwnersError(f"{where}.package must be a bare identifier")
    kind = e.get("kind")
    if kind not in KINDS:
        raise OwnersError(f"{where}.kind {kind!r} is not one of {sorted(KINDS)}")
    obj = _name(f"{where}.object", e.get("object"), kind)
    owner = _name(f"{where}.owner", e.get("owner"), "role")
    args = e.get("args")
    if kind == "function":
        if not isinstance(args, str) or not _ARGS_RE.match(args):
            raise OwnersError(f"{where}.args must be a signature string with safe characters")
        if "." not in obj:
            raise OwnersError(f"{where}.object must be schema-qualified for a function")
    elif args is not None:
        raise OwnersError(f"{where}.args is only valid for a function")
    return OwnerEntry(package=package, object=obj, kind=kind, owner=owner, args=args)


def _check_grant(index: int, g: Any) -> GrantEntry:
    where = f"grants[{index}]"
    if not isinstance(g, dict):
        raise OwnersError(f"{where} must be an object")
    package = g.get("package")
    if not isinstance(package, str) or not _BARE_RE.match(package):
        raise OwnersError(f"{where}.package must be a bare identifier")
    kind = g.get("kind")
    if kind not in KINDS:
        raise OwnersError(f"{where}.kind {kind!r} is not one of {sorted(KINDS)}")
    obj = _name(f"{where}.object", g.get("object"), kind)
    to = _name(f"{where}.to", g.get("to"), "role")
    privs = g.get("privileges")
    if not isinstance(privs, list) or not privs:
        raise OwnersError(f"{where}.privileges must be a non-empty list")
    for p in privs:
        if p not in PRIVILEGES:
            raise OwnersError(f"{where}.privileges has {p!r}; allowed: {sorted(PRIVILEGES)}")
    return GrantEntry(package=package, object=obj, kind=kind, privileges=tuple(privs), to=to)


def load_owners(source: Any = None) -> OwnerList:
    """Load and validate the owner list. ``source`` is a dict for tests."""
    if source is None:
        text = resources.files("zoid6").joinpath("sql", OWNERS_FILE).read_text(encoding="utf-8")
        try:
            data = json.loads(text)
        except json.JSONDecodeError as exc:
            raise OwnersError(f"{OWNERS_FILE} is not valid JSON: {exc}") from exc
    else:
        data = source
    if not isinstance(data, dict):
        raise OwnersError(f"{OWNERS_FILE} must be a JSON object")
    if data.get("schema_version") != SCHEMA_VERSION:
        raise OwnersError(
            f"{OWNERS_FILE} schema_version must be {SCHEMA_VERSION}, got {data.get('schema_version')!r}"
        )
    raw_entries = data.get("entries")
    if not isinstance(raw_entries, list):
        raise OwnersError("entries must be a list")
    entries = tuple(_check_owner(i, e) for i, e in enumerate(raw_entries))
    seen = set()
    for e in entries:
        key = (e.object, e.kind, e.args)
        if key in seen:
            raise OwnersError(f"duplicate owner entry for {e.kind} {e.object}")
        seen.add(key)
    raw_grants = data.get("grants", [])
    if not isinstance(raw_grants, list):
        raise OwnersError("grants must be a list")
    grants = tuple(_check_grant(i, g) for i, g in enumerate(raw_grants))
    return OwnerList(entries=entries, grants=grants)


def _quote(name: str) -> str:
    """Quote a validated identifier. Names are already restricted to [a-z0-9_.]."""
    return ".".join(f'"{part}"' for part in name.split("."))


def _object_sql(kind: str, obj: str, args: str | None) -> str:
    if kind == "function":
        return f"FUNCTION {_quote(obj)}({args})"
    if kind == "sequence":
        return f"SEQUENCE {_quote(obj)}"
    if kind == "view":
        return f"VIEW {_quote(obj)}"
    return f"TABLE {_quote(obj)}"


def owner_statements(owners: OwnerList, enabled_packages: set[str]) -> list[str]:
    """SQL for every entry whose package is enabled, in file order.

    Grants are appended after all owner changes. Disabled packages produce nothing.
    """
    stmts: list[str] = []
    for e in owners.entries:
        if e.package not in enabled_packages:
            continue
        stmts.append(f"ALTER {_object_sql(e.kind, e.object, e.args)} OWNER TO {_quote(e.owner)};")
    for g in owners.grants:
        if g.package not in enabled_packages:
            continue
        privs = ", ".join(sorted(g.privileges))
        target = _object_sql(g.kind, g.object, None)
        stmts.append(f"GRANT {privs} ON {target} TO {_quote(g.to)};")
    return stmts


class OwnerStepRefused(RuntimeError):
    """The owner step did not run: a gate, role, or statement check failed."""


def _missing_roles(conn, roles: set[str]) -> list[str]:
    with conn.cursor() as cur:
        cur.execute("SELECT rolname FROM pg_roles WHERE rolname = ANY(%s)", (sorted(roles),))
        present = {row[0] for row in cur.fetchall()}
    return sorted(roles - present)


def apply(args: Any, owners: OwnerList, enabled_packages: set[str], *, gate_ok: bool, conn: Any) -> list[str]:
    """Run the owner statements for enabled packages in one transaction.

    Refuses (raising OwnerStepRefused, changing nothing) unless ``gate_ok`` is true,
    and unless every target role already exists. Returns the statements that ran.
    The caller must be a superuser; the database refuses ALTER ... OWNER otherwise,
    and that error is raised, not swallowed.
    """
    if gate_ok is not True:
        raise OwnerStepRefused("owner step refused: the package gate did not pass")
    stmts = owner_statements(owners, enabled_packages)
    targets = {e.owner for e in owners.entries if e.package in enabled_packages}
    targets |= {g.to for g in owners.grants if g.package in enabled_packages}
    missing = _missing_roles(conn, targets)
    if missing:
        raise OwnerStepRefused(
            f"owner step refused: owner role(s) missing: {', '.join(missing)}. "
            "Run the install that creates them first (bed --install, casino --install)."
        )
    with conn.cursor() as cur:
        for stmt in stmts:
            cur.execute(stmt)
    conn.commit()
    return stmts
