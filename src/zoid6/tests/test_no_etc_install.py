"""zoid6 reads nothing from /etc and installs nothing there.

A read-only root filesystem makes any /etc write fail, and a daemon that reads /etc breaks
there. The configuration is the packaged zoid6.json, or $ZOID6_CONFIG.
"""

import pathlib
import re

import pytest

ROOT = pathlib.Path(__file__).resolve().parents[3]
ETC_PATH = re.compile(r"""(?<![\w.])/etc/""")
RUNTIME = [p for p in (ROOT / "src" / "zoid6").rglob("*.py") if "tests" not in p.parts]
DAEMON = ROOT / "src" / "zoid6" / "daemon"

# OS files named in messages, not config or install paths. Not reads.
ALLOWED = ("/etc/hosts",)


def _code(line):
    return line.split("#", 1)[0]


@pytest.mark.unit
def test_runtime_code_does_not_read_etc():
    offenders = []
    for path in RUNTIME:
        for n, line in enumerate(path.read_text().splitlines(), 1):
            code = _code(line)
            if ETC_PATH.search(code) and not any(a in code for a in ALLOWED):
                offenders.append(f"{path.relative_to(ROOT)}:{n}: {line.strip()}")
    assert not offenders, "runtime code reads /etc:\n" + "\n".join(offenders)


@pytest.mark.unit
def test_daemon_files_do_not_reference_etc():
    for path in sorted(p for p in DAEMON.glob("*") if p.is_file()):
        text = "\n".join(l for l in path.read_text().splitlines() if not l.lstrip().startswith("#"))
        assert not ETC_PATH.search(text), f"{path.name} references /etc"


@pytest.mark.unit
def test_no_factory_tree_or_etc_install_targets():
    text = (ROOT / "src" / "Makefile").read_text()
    assert "install-etc" not in text
    assert "usr/share/factory" not in text
    assert not re.search(r"^\s*ETC_DIR\b", text, re.M)


@pytest.mark.unit
def test_systemd_unit_is_not_copied_into_etc():
    """The unit is installed to /usr/lib/systemd/system/ only. A copy in /etc/systemd/system/
    overrides the vendor unit and writes under /etc, so no install target may write there."""
    text = (ROOT / "src" / "Makefile").read_text()
    code = "\n".join(l for l in text.splitlines() if not l.lstrip().startswith("#"))
    assert "/etc/systemd/system" not in code


@pytest.mark.unit
def test_install_guide_names_the_zoid6_unit_source():
    text = (ROOT / "handbook" / "INSTALL.md").read_text()
    assert "zoid6/src/zoid6/daemon/zoid6.service" in text
    assert "bed/src/bed/daemon/zoid6.service" not in text


@pytest.mark.unit
def test_user_unit_is_a_user_service_in_the_user_directory():
    """The login-user unit is zoid6.service under daemon/user/, installed to the user manager's dir."""
    daemon = ROOT / "src" / "zoid6" / "daemon"
    user_unit = daemon / "user" / "zoid6.service"
    assert user_unit.is_file()
    assert not (daemon / "zoid6-user.service").exists()
    text = user_unit.read_text()
    assert "WantedBy=default.target" in text
    assert "%h/.local/zoid6" in text
    assert "User=" not in text, "a user unit runs as the logged-in user; it must not set User="
    assert "/etc/" not in text
    makefile = (ROOT / "src" / "Makefile").read_text()
    assert "SYSTEMD_USER_DIR ?= $(HOME)/.config/systemd/user" in makefile
    assert "systemctl --user" in makefile


@pytest.mark.unit
def test_user_install_target_is_in_the_goal_filter():
    """install-systemd-user must be in the MAKECMDGOALS filter, or PREFIX and the dirs are empty."""
    text = (ROOT / "src" / "Makefile").read_text()
    filter_line = next(l for l in text.splitlines() if "MAKECMDGOALS" in l and "install-user" in l)
    assert "install-systemd-user" in filter_line


@pytest.mark.unit
def test_user_install_refuses_root_and_the_zoid6_account():
    text = (ROOT / "src" / "Makefile").read_text()
    assert 'test "$$(id -u)" -ne 0' in text
    assert 'test "$$(id -un)" != "zoid6"' in text


@pytest.mark.unit
def test_user_install_builds_every_wheel_from_a_clean_copy():
    """No wheel is built in place: a repo's build/ can be owned by another account and break pip."""
    text = (ROOT / "src" / "Makefile").read_text()
    assert "define WHEEL_FROM_CLEAN_COPY" in text
    assert "--exclude=./build" in text
    for call in ("BBSENGINE_DIR", "BED_DIR", "CASINO_DIR", "CURDIR"):
        assert f"$(call WHEEL_FROM_CLEAN_COPY,$({call}))" in text or f"$(call WHEEL_FROM_CLEAN_COPY,{call})" in text or call in text
    assert "pip wheel --no-deps --no-build-isolation --no-clean --wheel-dir $(WHEEL_DIR) $(BED_DIR)" not in text
