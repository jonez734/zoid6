"""zoid6's package list and the install gate that checks it.

``data/packages.json`` lists the programs whose objects zoid6's owner step
touches, in install order. Only ``enabled`` entries are checked, refused on,
or transferred. A disabled entry is never read for its manifest, checked, or
refused on, so adding a program to the list is safe until it is enabled.

Public surface:

  load_packages() -> PackageList          validates data/packages.json
  gate(args, packages, conn=...) -> Gate  checks each enabled package's manifest in order

``gate`` never changes the database. It reports, and the caller refuses to run the
owner step unless ``gate.ok`` is true.
"""

from __future__ import annotations

import json
from dataclasses import dataclass, field
from importlib import resources
from typing import Any

SCHEMA_VERSION = 1
PACKAGES_FILE = "packages.json"


class PackagesError(ValueError):
    """Raised when packages.json does not validate."""


@dataclass(frozen=True)
class Package:
    name: str
    enabled: bool
    manifest: str
    install: str


@dataclass(frozen=True)
class PackageList:
    packages: tuple[Package, ...]

    @property
    def enabled(self) -> tuple[Package, ...]:
        return tuple(p for p in self.packages if p.enabled)


@dataclass
class Gate:
    ok: bool
    results: list[tuple[Package, Any]] = field(default_factory=list)


def _check_entry(index: int, entry: Any) -> Package:
    where = f"packages[{index}]"
    if not isinstance(entry, dict):
        raise PackagesError(f"{where} must be an object")
    name = entry.get("name")
    enabled = entry.get("enabled")
    manifest = entry.get("manifest")
    install = entry.get("install")
    if not isinstance(name, str) or not name:
        raise PackagesError(f"{where}.name must be a non-empty string")
    if not isinstance(enabled, bool):
        raise PackagesError(f"{where}.enabled must be true or false")
    if not isinstance(manifest, str) or not manifest.endswith("/sql/objects.json"):
        raise PackagesError(f"{where}.manifest must end in /sql/objects.json")
    if not isinstance(install, str) or not install:
        raise PackagesError(f"{where}.install must name the command to run")
    return Package(name=name, enabled=enabled, manifest=manifest, install=install)


def load_packages(source: Any = None) -> PackageList:
    """Load and validate the package list.

    ``source`` is a dict for tests; by default the packaged ``data/packages.json``
    is read. Raises PackagesError for any violation: wrong schema_version,
    duplicate names, an enabled package that depends on a disabled one, or
    a malformed entry.
    """
    if source is None:
        text = resources.files("zoid6").joinpath("data", PACKAGES_FILE).read_text(encoding="utf-8")
        try:
            data = json.loads(text)
        except json.JSONDecodeError as exc:
            raise PackagesError(f"{PACKAGES_FILE} is not valid JSON: {exc}") from exc
    else:
        data = source
    if not isinstance(data, dict):
        raise PackagesError(f"{PACKAGES_FILE} must be a JSON object")
    if data.get("schema_version") != SCHEMA_VERSION:
        raise PackagesError(
            f"{PACKAGES_FILE} schema_version must be {SCHEMA_VERSION}, got {data.get('schema_version')!r}"
        )
    raw = data.get("packages")
    if not isinstance(raw, list) or not raw:
        raise PackagesError("packages must be a non-empty list")
    packages = tuple(_check_entry(i, e) for i, e in enumerate(raw))

    names = [p.name for p in packages]
    dupes = sorted({n for n in names if names.count(n) > 1})
    if dupes:
        raise PackagesError(f"duplicate package names: {', '.join(dupes)}")

    # Dependencies: zoid6 depends on bbsengine6, bed depends on bbsengine6,
    # casino depends on bed. An enabled package must not depend on a disabled one.
    deps = {"bed": ("bbsengine6",), "casino": ("bed",)}
    enabled = {p.name for p in packages if p.enabled}
    for name in sorted(enabled):
        for dep in deps.get(name, ()):
            if dep not in enabled:
                raise PackagesError(
                    f"enabled package '{name}' depends on '{dep}', which is not enabled"
                )
    return PackageList(packages=packages)


def _load_manifest(pkg: Package):
    """Load one package's manifest from the installed package that owns it.

    The manifest path is ``<package>/sql/objects.json``; the first segment is the
    importable package. Raises if it is missing or invalid, which the gate reports
    as a refusal rather than a pass.
    """
    from bbsengine6.backend import objects

    owner, _, rest = pkg.manifest.partition("/")
    text = resources.files(owner).joinpath(rest).read_text(encoding="utf-8")
    return objects.load_manifest(json.loads(text))


def gate(args: Any, packages: PackageList, **kwargs: Any) -> Gate:
    """Check each enabled package's manifest, in list order. Read-only.

    Returns ``Gate(ok=True)`` only when every enabled package is ``installed``.
    A manifest that cannot be read or validated counts as not installed. Disabled
    packages are not read, checked, or refused on.
    """
    from bbsengine6.backend import objects

    conn = kwargs.get("conn")
    pool = kwargs.get("pool")
    result = Gate(ok=True)
    for pkg in packages.enabled:
        try:
            manifest = _load_manifest(pkg)
            report = objects.check_objects(args, manifest, conn=conn, pool=pool)
        except Exception as exc:  # noqa: BLE001 - an unreadable manifest is a refusal, not a pass
            result.ok = False
            result.results.append((pkg, exc))
            continue
        if report.status != objects.STATUS_INSTALLED:
            result.ok = False
        result.results.append((pkg, report))
    return result
