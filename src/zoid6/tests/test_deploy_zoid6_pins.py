"""deploy_zoid6.sh installs the newest build of each package on its version line.

The script names no wheel: scripts/resolve_wheels.py picks one per package from the
wheels directory. These tests pin that rule, using a temporary directory of fake
wheel names, and check the script itself names no filename.
"""

import importlib.util
import pathlib
import re
import subprocess
import sys

import pytest

SCRIPTS = pathlib.Path(__file__).resolve().parents[3] / "scripts"
RESOLVE = SCRIPTS / "resolve_wheels.py"
DEPLOY = SCRIPTS / "deploy_zoid6.sh"


def _load_resolver():
    spec = importlib.util.spec_from_file_location("resolve_wheels", RESOLVE)
    mod = importlib.util.module_from_spec(spec)
    spec.loader.exec_module(mod)
    return mod


@pytest.fixture
def wheels(tmp_path):
    names = [
        # bed: an old line, then two builds on the current 0.4.0.dev line
        "bed-0.0.3.dev20261028130812-py3-none-any.whl",
        "bed-0.4.0.dev20261028100000-py3-none-any.whl",
        "bed-0.4.0.dev20261028174838-py3-none-any.whl",
        # bbsengine6: one build on the current line
        "bbsengine6-0.4.0.dev20261028174838-py3-none-any.whl",
        # letteredolive: a bare-timestamp build sorts above the real line, and must be ignored
        "letteredolive-0.0.2.dev20261028110148-py3-none-any.whl",
        "letteredolive-202610232249-py3-none-any.whl",
        "letteredolive-0.0.2.dev20261028174838-py3-none-any.whl",
    ]
    for n in names:
        (tmp_path / n).write_bytes(b"")
    return tmp_path


@pytest.mark.unit
def test_newest_build_on_the_version_line_wins(wheels):
    r = _load_resolver()
    assert r.resolve(str(wheels), "bed").endswith("bed-0.4.0.dev20261028174838-py3-none-any.whl")


@pytest.mark.unit
def test_bare_timestamp_builds_are_not_picked(wheels):
    r = _load_resolver()
    got = r.resolve(str(wheels), "letteredolive")
    assert got.endswith("letteredolive-0.0.2.dev20261028174838-py3-none-any.whl"), got


@pytest.mark.unit
def test_missing_package_fails_with_a_clear_error(wheels):
    r = _load_resolver()
    with pytest.raises(KeyError, match="casino"):
        r.resolve(str(wheels), "casino")


@pytest.mark.unit
def test_cli_prints_one_path_per_package(wheels):
    proc = subprocess.run(
        [sys.executable, str(RESOLVE), str(wheels), "bed", "bbsengine6", "letteredolive"],
        capture_output=True, text=True,
    )
    assert proc.returncode == 0, proc.stderr
    names = [pathlib.Path(x).name.split("-")[0] for x in proc.stdout.split()]
    assert names == ["bed", "bbsengine6", "letteredolive"]


@pytest.mark.unit
def test_cli_fails_when_a_package_has_no_build_on_its_line(wheels):
    proc = subprocess.run(
        [sys.executable, str(RESOLVE), str(wheels), "letteredolive", "casino"],
        capture_output=True, text=True,
    )
    assert proc.returncode == 1
    assert "casino" in proc.stderr


@pytest.mark.unit
def test_deploy_script_names_no_wheel_filename():
    text = DEPLOY.read_text()
    assert not re.search(r"[a-z0-9_]+-\d+\.\d+\.\d+\.dev\d+", text), "a wheel filename is hard-coded"
    assert not re.search(r"^\w+_WHEEL=", text, re.MULTILINE), "a *_WHEEL pin is hard-coded"
    assert "resolve_wheels.py" in text


@pytest.mark.unit
def test_deploy_script_defaults_to_the_zoid6_account_home():
    text = DEPLOY.read_text()
    assert 'ZOID6_HOME="${ZOID6_HOME:-/home/zoid6}"' in text
    assert "NEEDINFO" not in re.sub(r"#.*", "", text).replace("*NEEDINFO*", ""), (
        "a NEEDINFO placeholder is a default value"
    )


@pytest.mark.unit
def test_deploy_script_rejects_a_needinfo_placeholder_by_name():
    # The guard must name the variable it rejects, so the operator knows what to set.
    text = DEPLOY.read_text()
    assert "*NEEDINFO*)" in text
    assert "is not set (value:" in text
