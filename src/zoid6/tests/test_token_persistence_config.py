"""zoid6 config selects the database-backed token store.

bed reads token_persistence from the top-level ``auth`` section. The packaged zoid6 config
must set "db", so a fresh install gets the database-backed token store.
"""

import json
from pathlib import Path

import zoid6

ROOT = Path(zoid6.__file__).resolve().parents[2]
SRC = ROOT / "src" / "zoid6" / "data" / "zoid6.json"


def _persistence(path: Path):
    cfg = json.loads(path.read_text())
    return cfg.get("auth", {}).get("token_persistence")


def test_source_config_selects_db():
    assert _persistence(SRC) == "db"


def _session_idle(path: Path):
    cfg = json.loads(path.read_text())
    return cfg.get("session", {}).get("idle_minutes")


def test_source_config_sets_session_idle_minutes():
    assert _session_idle(SRC) == 15


