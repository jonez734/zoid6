"""zoid6's startup-log settings match bed's, and the daemon writes the file with the right mode.

bed.json owns the startup_log section; zoid6.json must carry the same keys and values, so a deploy
cannot run zoid6 with a different log path or rotation than bed declares. The unit sets UMask=0027 so
the log is created 0640, and the log directory is 0750.
"""

import json
import pathlib

import pytest

ROOT = pathlib.Path(__file__).resolve().parents[3]
ZOID6_JSON = ROOT / "src" / "zoid6" / "data" / "zoid6.json"
BED_JSON = ROOT.parent / "bed" / "src" / "bed" / "data" / "bed.json"
UNIT = ROOT / "src" / "zoid6" / "daemon" / "zoid6.service"
TMPFILES = ROOT / "src" / "zoid6" / "daemon" / "zoid6.tmpfiles"
KEYS = ("path", "max_bytes", "backups")


@pytest.mark.unit
def test_zoid6_startup_log_matches_bed_json():
    zoid = json.loads(ZOID6_JSON.read_text())["startup_log"]
    bed = json.loads(BED_JSON.read_text())["startup_log"]
    for key in KEYS:
        assert zoid[key] == bed[key], f"startup_log.{key}: zoid6.json={zoid[key]!r} bed.json={bed[key]!r}"


@pytest.mark.unit
def test_zoid6_startup_log_path_is_the_opencode_readable_file():
    assert json.loads(ZOID6_JSON.read_text())["startup_log"]["path"] == "/var/log/zoid6/startup.log"


@pytest.mark.unit
def test_unit_sets_umask_0027():
    lines = [ln.strip() for ln in UNIT.read_text().splitlines() if not ln.lstrip().startswith("#")]
    assert "UMask=0027" in lines


@pytest.mark.unit
def test_log_directory_is_0750_owned_by_zoid6():
    rows = [ln.split() for ln in TMPFILES.read_text().splitlines() if ln.startswith("d") and "/var/log/zoid6" in ln]
    assert rows, "the log directory must be declared in zoid6.tmpfiles"
    _type, path, mode, owner, group = rows[0][:5]
    assert (path, mode, owner, group) == ("/var/log/zoid6", "0750", "zoid6", "zoid6")
