"""zoid6 config selects the database-backed token store.

bed reads token_persistence from the top-level ``auth`` section. The shipped
zoid6 config and its factory copy must both set "db", and must agree, so a
fresh install and a live install get the same behaviour.
"""

import json
from pathlib import Path

import zoid6

ROOT = Path(zoid6.__file__).resolve().parents[2]
SRC = ROOT / "src" / "zoid6" / "data" / "zoid6.json"
FACTORY = ROOT / "usr" / "share" / "factory" / "etc" / "zoid6" / "zoid6.json"


def _persistence(path: Path):
    cfg = json.loads(path.read_text())
    return cfg.get("auth", {}).get("token_persistence")


def test_source_config_selects_db():
    assert _persistence(SRC) == "db"


def test_factory_config_selects_db():
    assert _persistence(FACTORY) == "db"


def test_source_and_factory_agree():
    assert _persistence(SRC) == _persistence(FACTORY)
