"""zoid6's package list and install gate (phase 8).

Disabled packages must never be read for their manifest, checked, or refused on.
The gate passes only when every enabled package is ``installed``. These tests use
fake manifests and reports, so no database is needed.
"""

from argparse import Namespace
from copy import deepcopy
from unittest.mock import patch

import pytest

from bbsengine6.backend import objects
from zoid6 import packages as pk


def _pkg(name, enabled, manifest=None, install=None):
    return {
        "name": name,
        "enabled": enabled,
        "manifest": manifest or f"{name}/sql/objects.json",
        "install": install or f"{name} --install",
    }


def _data(*entries):
    return {"schema_version": 1, "packages": list(entries)}


STANDARD = _data(
    _pkg("bbsengine6", True),
    _pkg("bed", True),
    _pkg("casino", True),
    _pkg("empyre", False),
)


# ---- the packaged list (8.1) -------------------------------------------------


def test_packaged_list_loads_and_is_ordered():
    pl = pk.load_packages()
    names = [p.name for p in pl.packages]
    assert names[:3] == ["bbsengine6", "bed", "casino"]
    assert set(names[3:]) == {"empyre", "murdermotel", "zoidoffice", "teos"}


def test_packaged_enabled_set_is_the_three_core_packages():
    assert [p.name for p in pk.load_packages().enabled] == ["bbsengine6", "bed", "casino"]


def test_disabled_entries_are_not_enabled():
    pl = pk.load_packages()
    for p in pl.packages:
        if p.name in ("empyre", "murdermotel", "zoidoffice", "teos"):
            assert p.enabled is False


# ---- validation ---------------------------------------------------------------


def test_schema_version_must_be_1():
    d = deepcopy(STANDARD)
    d["schema_version"] = 2
    with pytest.raises(pk.PackagesError, match="schema_version"):
        pk.load_packages(d)


def test_duplicate_names_are_refused():
    d = _data(_pkg("bed", True), _pkg("bed", False))
    with pytest.raises(pk.PackagesError, match="duplicate"):
        pk.load_packages(d)


def test_enabled_package_cannot_depend_on_a_disabled_one():
    d = _data(_pkg("bbsengine6", False), _pkg("bed", True))
    with pytest.raises(pk.PackagesError, match="depends on 'bbsengine6'"):
        pk.load_packages(d)


def test_casino_requires_bed_enabled():
    d = _data(_pkg("bbsengine6", True), _pkg("bed", False), _pkg("casino", True))
    with pytest.raises(pk.PackagesError, match="depends on 'bed'"):
        pk.load_packages(d)


@pytest.mark.parametrize(
    "bad_entry",
    [
        {"name": "x", "enabled": "yes", "manifest": "x/sql/objects.json", "install": "x --install"},
        {"name": "x", "enabled": True, "manifest": "x/objects.txt", "install": "x --install"},
        {"name": "x", "enabled": True, "manifest": "x/sql/objects.json", "install": ""},
        {"name": "", "enabled": True, "manifest": "x/sql/objects.json", "install": "x --install"},
    ],
)
def test_malformed_entries_are_refused(bad_entry):
    with pytest.raises(pk.PackagesError):
        pk.load_packages(_data(bad_entry))


def test_disabled_package_with_bad_dependency_is_not_refused():
    """A disabled package's dependencies are not checked: it is never touched."""
    d = _data(_pkg("bbsengine6", True), _pkg("bed", True), _pkg("empyre", False))
    assert [p.name for p in pk.load_packages(d).enabled] == ["bbsengine6", "bed"]


# ---- the gate (8.2 and 8.3) ----------------------------------------------------


def _fake_check(status_by_project):
    def _check(args, manifest, **kw):
        status = status_by_project.get(manifest.project, objects.STATUS_INSTALLED)
        missing = [] if status == objects.STATUS_INSTALLED else [
            objects.ObjectRef("table", f"{manifest.project}.missing")
        ]
        return objects.Report(project=manifest.project, status=status, present=[], missing=missing)

    return _check


def _manifest_for(pkg):
    return objects.load_manifest({
        "schema_version": 1,
        "project": pkg.name,
        "objects": [{"kind": "table", "name": f"{pkg.name}.t"}],
    })


@pytest.fixture
def standard_list():
    return pk.load_packages(STANDARD)


def test_gate_passes_when_every_enabled_package_is_installed(standard_list, monkeypatch):
    monkeypatch.setattr(pk, "_load_manifest", _manifest_for)
    with patch.object(objects, "check_objects", side_effect=_fake_check({})):
        g = pk.gate(Namespace(), standard_list, conn=object())
    assert g.ok is True
    assert [r.status for _, r in g.results] == [objects.STATUS_INSTALLED] * 3


def test_gate_refuses_when_one_enabled_package_is_partial(standard_list, monkeypatch):
    monkeypatch.setattr(pk, "_load_manifest", _manifest_for)
    with patch.object(objects, "check_objects",
                      side_effect=_fake_check({"bed": objects.STATUS_PARTIAL})):
        g = pk.gate(Namespace(), standard_list, conn=object())
    assert g.ok is False
    statuses = {p.name: r.status for p, r in g.results}
    assert statuses["bed"] == objects.STATUS_PARTIAL
    assert statuses["bbsengine6"] == objects.STATUS_INSTALLED


def test_gate_refuses_when_a_package_is_not_installed(standard_list, monkeypatch):
    monkeypatch.setattr(pk, "_load_manifest", _manifest_for)
    with patch.object(objects, "check_objects",
                      side_effect=_fake_check({"casino": objects.STATUS_NOT_INSTALLED})):
        g = pk.gate(Namespace(), standard_list, conn=object())
    assert g.ok is False


def test_gate_never_reads_a_disabled_manifest(monkeypatch):
    """Disabled packages are not loaded, checked or refused on."""
    seen = []

    def _load(pkg):
        seen.append(pkg.name)
        return _manifest_for(pkg)

    monkeypatch.setattr(pk, "_load_manifest", _load)
    pl = pk.load_packages(STANDARD)
    with patch.object(objects, "check_objects", side_effect=_fake_check({})):
        pk.gate(Namespace(), pl, conn=object())
    assert "empyre" not in seen
    assert seen == ["bbsengine6", "bed", "casino"]


def test_gate_checks_in_list_order(standard_list, monkeypatch):
    seen = []

    def _check(args, manifest, **kw):
        seen.append(manifest.project)
        return _fake_check({})(args, manifest, **kw)

    monkeypatch.setattr(pk, "_load_manifest", _manifest_for)
    with patch.object(objects, "check_objects", side_effect=_check):
        pk.gate(Namespace(), standard_list, conn=object())
    assert seen == ["bbsengine6", "bed", "casino"]


def test_unreadable_manifest_is_a_refusal_not_a_pass(standard_list, monkeypatch):
    def _boom(pkg):
        raise FileNotFoundError(pkg.manifest)

    monkeypatch.setattr(pk, "_load_manifest", _boom)
    g = pk.gate(Namespace(), standard_list, conn=object())
    assert g.ok is False
    assert all(isinstance(r, Exception) for _, r in g.results)


def test_empty_enabled_set_is_not_a_pass_by_default():
    """With no enabled packages the gate has nothing to refuse on; it reports ok, so
    zoid6 install must always list at least bbsengine6. The packaged list does."""
    d = _data(_pkg("bbsengine6", False))
    g = pk.gate(Namespace(), pk.load_packages(d), conn=object())
    assert g.ok is True and g.results == []
