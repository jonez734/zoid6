"""zoid6's owner list, generator and owner step (phase 9).

Uses fake connections and no database. The superuser ALTER itself is checked
separately, under jam's login.
"""

from copy import deepcopy
from unittest.mock import MagicMock

import pytest

from zoid6 import owners as ow


def _base():
    return {
        "schema_version": 1,
        "entries": [
            {"package": "bed", "object": "engine.__bed_token", "kind": "table", "owner": "bed_obj"},
            {"package": "bed", "object": "engine.bed_token_get", "kind": "function",
             "args": "bytea", "owner": "bed_obj"},
            {"package": "casino", "object": "casino.__table", "kind": "table", "owner": "casino_obj"},
            {"package": "empyre", "object": "empyre.thing", "kind": "table", "owner": "empyre_obj"},
        ],
        "grants": [
            {"package": "bed", "object": "casino.__table", "kind": "table",
             "privileges": ["SELECT"], "to": "bed_obj"},
        ],
    }


def _changed(cur):
    """The SQL that changes ownership or privileges. The role lookup is a read and is excluded."""
    return [c.args[0] for c in cur.execute.call_args_list
            if c.args and str(c.args[0]).lstrip().upper().startswith(("ALTER", "GRANT"))]


# ---- the packaged list ----------------------------------------------------------


def test_packaged_owner_list_loads():
    ol = ow.load_owners()
    assert len(ol.entries) == 74
    assert {e.package for e in ol.entries} == {"bed", "casino", "postoffice"}


def test_packaged_list_targets_only_the_owner_roles():
    assert {e.owner for e in ow.load_owners().entries} == {"bed_obj", "casino_obj", "postoffice_obj"}


def test_casino_schema_is_not_in_the_list():
    """The casino schema stays with zoid6 (option A), so it is not transferred."""
    for e in ow.load_owners().entries:
        assert not (e.kind == "schema")
        assert e.object != "casino"


# ---- validation ------------------------------------------------------------------


def test_schema_version_must_be_1():
    d = _base()
    d["schema_version"] = 3
    with pytest.raises(ow.OwnersError, match="schema_version"):
        ow.load_owners(d)


@pytest.mark.parametrize(
    "field,value",
    [
        ("object", "engine.x; DROP TABLE y"),
        ("object", "Engine.X"),
        ("owner", 'bed_obj"; DROP'),
        ("owner", ""),
    ],
)
def test_unsafe_identifiers_are_refused(field, value):
    d = _base()
    d["entries"][0][field] = value
    with pytest.raises(ow.OwnersError):
        ow.load_owners(d)


def test_unknown_kind_is_refused():
    d = _base()
    d["entries"][0]["kind"] = "index"
    with pytest.raises(ow.OwnersError, match="kind"):
        ow.load_owners(d)


def test_function_needs_args_and_schema_qualified_name():
    d = _base()
    del d["entries"][1]["args"]
    with pytest.raises(ow.OwnersError, match="args"):
        ow.load_owners(d)
    d = _base()
    d["entries"][1]["object"] = "bed_token_get"
    with pytest.raises(ow.OwnersError, match="schema-qualified"):
        ow.load_owners(d)


def test_args_with_injection_characters_are_refused():
    d = _base()
    d["entries"][1]["args"] = "bytea); DROP TABLE x; --"
    with pytest.raises(ow.OwnersError, match="signature"):
        ow.load_owners(d)


def test_grant_privilege_must_be_on_the_allow_list():
    d = _base()
    d["grants"][0]["privileges"] = ["SELECT", "TRUNCATE"]
    with pytest.raises(ow.OwnersError, match="allowed"):
        ow.load_owners(d)


def test_duplicate_owner_entry_is_refused():
    d = _base()
    d["entries"].append(deepcopy(d["entries"][0]))
    with pytest.raises(ow.OwnersError, match="duplicate"):
        ow.load_owners(d)


# ---- generator (9.2) --------------------------------------------------------------


def test_only_enabled_packages_produce_statements():
    stmts = ow.owner_statements(ow.load_owners(_base()), {"bed", "casino"})
    assert not any("empyre" in s for s in stmts)
    assert len(stmts) == 4  # 3 owner changes + 1 grant, empyre excluded


def test_disabled_package_produces_nothing():
    stmts = ow.owner_statements(ow.load_owners(_base()), {"empyre"})
    assert stmts == ['ALTER TABLE "empyre"."thing" OWNER TO "empyre_obj";']


def test_statements_are_quoted_identifiers():
    stmts = ow.owner_statements(ow.load_owners(_base()), {"bed"})
    assert stmts[0] == 'ALTER TABLE "engine"."__bed_token" OWNER TO "bed_obj";'


def test_function_statement_includes_signature():
    stmts = ow.owner_statements(ow.load_owners(_base()), {"bed"})
    assert 'ALTER FUNCTION "engine"."bed_token_get"(bytea) OWNER TO "bed_obj";' in stmts


def test_grants_follow_owner_changes():
    stmts = ow.owner_statements(ow.load_owners(_base()), {"bed"})
    assert stmts[-1] == 'GRANT SELECT ON TABLE "casino"."__table" TO "bed_obj";'


def test_no_enabled_package_produces_no_statements():
    assert ow.owner_statements(ow.load_owners(_base()), set()) == []


# ---- owner step (9.3) --------------------------------------------------------------


def _conn(present_roles):
    conn = MagicMock()
    cur = MagicMock()
    cur.fetchall.return_value = [(r,) for r in present_roles]
    conn.cursor.return_value.__enter__.return_value = cur
    return conn, cur


def test_refuses_when_the_gate_did_not_pass():
    conn, cur = _conn({"bed_obj", "casino_obj"})
    with pytest.raises(ow.OwnerStepRefused, match="gate did not pass"):
        ow.apply(None, ow.load_owners(_base()), {"bed", "casino"}, gate_ok=False, conn=conn)
    assert _changed(cur) == []
    conn.commit.assert_not_called()


def test_refuses_when_a_target_role_is_missing_and_changes_nothing():
    conn, cur = _conn({"bed_obj"})  # casino_obj is missing
    with pytest.raises(ow.OwnerStepRefused, match="casino_obj"):
        ow.apply(None, ow.load_owners(_base()), {"bed", "casino"}, gate_ok=True, conn=conn)
    assert _changed(cur) == []
    conn.commit.assert_not_called()


def test_runs_statements_in_one_transaction_when_ready():
    conn, cur = _conn({"bed_obj", "casino_obj"})
    ran = ow.apply(None, ow.load_owners(_base()), {"bed", "casino"}, gate_ok=True, conn=conn)
    assert len(ran) == 4
    assert _changed(cur) == ran
    conn.commit.assert_called_once()


def test_a_failed_statement_is_not_committed():
    conn, cur = _conn({"bed_obj", "casino_obj"})
    cur.execute.side_effect = [None, RuntimeError("permission denied")]
    with pytest.raises(RuntimeError):
        ow.apply(None, ow.load_owners(_base()), {"bed", "casino"}, gate_ok=True, conn=conn)
    conn.commit.assert_not_called()
