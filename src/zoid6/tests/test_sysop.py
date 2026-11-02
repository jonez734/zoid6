"""The zoid6 sysop command (phase 10.3 and 10.4): account check, prefix, --check-acl, --install.

No setfacl or pip runs. ACL reads and writes and the installer are faked, so these tests
need no zoid6 account and no ACL support on the host.
"""

from unittest.mock import patch

import pytest
from bbsengine6 import acl

from zoid6 import sysop

# ---- the account check -------------------------------------------------------------


def test_refuses_unless_running_as_zoid6(monkeypatch, capsys):
    monkeypatch.setattr(sysop, "effective_account", lambda: "opencode")
    rc = sysop.main(["--check-acl"])
    assert rc == 1
    assert "must run as the zoid6 account" in capsys.readouterr().err


def test_refusal_applies_to_install_too(monkeypatch):
    monkeypatch.setattr(sysop, "effective_account", lambda: "jam")
    with patch.object(sysop, "apply_acl") as apply, patch.object(sysop, "install_wheels") as inst:
        assert sysop.main(["--install"]) == 1
    apply.assert_not_called()
    inst.assert_not_called()


def test_account_comes_from_the_effective_uid_not_the_environment(monkeypatch):
    monkeypatch.setenv("USER", "zoid6")
    monkeypatch.setattr(sysop.os, "geteuid", lambda: 4242)
    monkeypatch.setattr(sysop.pwd, "getpwuid", lambda uid: type("P", (), {"pw_name": "opencode"})())
    assert sysop.effective_account() == "opencode"


# ---- prefix -------------------------------------------------------------------------


def test_default_paths_without_a_prefix():
    assert sysop._paths(None) == ("/home/zoid6/venv", "/home/zoid6/dist-wheels")


def test_prefix_moves_the_venv_and_puts_dist_beside_it():
    venv, dist = sysop._paths("/srv/z/venv")
    assert venv == "/srv/z/venv"
    assert dist == "/srv/z/dist-wheels"


# ---- --check-acl (10.4) --------------------------------------------------------------


def _fake_check(missing=()):
    def _check(path, group, want):
        return path not in missing

    return _check


def test_check_acl_reports_every_entry_read_only(monkeypatch, capsys):
    monkeypatch.setattr(sysop, "effective_account", lambda: "zoid6")
    with patch.object(acl, "check_acl", side_effect=_fake_check()) as check, \
         patch.object(acl, "grant_acl") as grant:
        rc = sysop.main(["--check-acl"])
    assert rc == 0
    grant.assert_not_called()
    assert check.call_count == len(acl.acl_plan())
    out = capsys.readouterr().out
    assert "all grants present" in out


def test_check_acl_fails_when_a_grant_is_missing(monkeypatch, capsys):
    monkeypatch.setattr(sysop, "effective_account", lambda: "zoid6")
    with patch.object(acl, "check_acl", side_effect=_fake_check(missing={"/home/zoid6/dist-wheels"})), \
         patch.object(acl, "grant_acl") as grant:
        rc = sysop.main(["--check-acl"])
    assert rc == 1
    grant.assert_not_called()
    out = capsys.readouterr().out
    assert "MISS" in out and "/home/zoid6/dist-wheels" in out
    assert "Run zoid6 --install" in out


# ---- --install (10.3) ----------------------------------------------------------------


def test_install_applies_acl_then_installs_then_checks(monkeypatch, capsys):
    monkeypatch.setattr(sysop, "effective_account", lambda: "zoid6")
    order = []
    monkeypatch.setattr(sysop, "apply_acl", lambda prefix=None: order.append("acl"))
    monkeypatch.setattr(sysop, "install_wheels", lambda prefix=None: order.append("wheels"))
    with patch.object(acl, "check_acl", return_value=True):
        rc = sysop.main(["--install"])
    assert rc == 0
    assert order == ["acl", "wheels"]
    assert "complete" in capsys.readouterr().out


def test_install_stops_before_wheels_when_acl_fails(monkeypatch):
    monkeypatch.setattr(sysop, "effective_account", lambda: "zoid6")

    def boom(prefix=None):
        raise sysop.SysopError("zoid6 --install: /home/zoid6/venv does not exist.")

    with patch.object(sysop, "install_wheels") as inst:
        monkeypatch.setattr(sysop, "apply_acl", boom)
        assert sysop.main(["--install"]) == 1
    inst.assert_not_called()


def test_apply_acl_refuses_a_missing_path_before_any_setfacl(monkeypatch, tmp_path):
    monkeypatch.setattr(sysop, "_paths", lambda prefix: (str(tmp_path / "nope"), str(tmp_path)))
    with patch.object(acl, "grant_acl") as grant, \
         pytest.raises(sysop.SysopError, match="does not exist"):
        sysop.apply_acl(None)
    grant.assert_not_called()


def test_install_wheels_refuses_when_the_installer_is_not_staged(monkeypatch, tmp_path):
    monkeypatch.setattr(sysop, "_paths", lambda prefix: (str(tmp_path / "venv"), str(tmp_path)))
    with pytest.raises(sysop.SysopError, match="not staged"):
        sysop.install_wheels(None)


def test_install_wheels_passes_dist_and_venv_to_the_installer(monkeypatch, tmp_path):
    dist = tmp_path / "dist"
    dist.mkdir()
    (dist / sysop.INSTALLER_NAME).write_text("#!/bin/sh\n")
    monkeypatch.setattr(sysop, "_paths", lambda prefix: ("/srv/v", str(dist)))
    with patch.object(sysop.subprocess, "run") as run:
        run.return_value.returncode = 0
        sysop.install_wheels("/srv/v")
    _, kwargs = run.call_args
    assert kwargs["env"]["DIST"] == str(dist)
    assert kwargs["env"]["VENV"] == "/srv/v"


def test_install_wheels_reports_an_installer_failure(monkeypatch, tmp_path):
    dist = tmp_path / "dist"
    dist.mkdir()
    (dist / sysop.INSTALLER_NAME).write_text("#!/bin/sh\n")
    monkeypatch.setattr(sysop, "_paths", lambda prefix: ("/srv/v", str(dist)))
    with patch.object(sysop.subprocess, "run") as run:
        run.return_value.returncode = 3
        with pytest.raises(sysop.SysopError, match="rc=3"):
            sysop.install_wheels(None)


def test_mode_is_required(capsys):
    with pytest.raises(SystemExit):
        sysop.main([])


def test_a_missing_later_path_changes_no_earlier_acl(monkeypatch, tmp_path):
    """Regression: a later missing path must refuse before an earlier path is granted."""
    home = tmp_path / "home"
    home.mkdir()
    monkeypatch.setattr(sysop, "_paths", lambda prefix: (str(tmp_path / "missing-venv"), str(home)))
    with patch.object(acl, "grant_acl") as grant, \
         pytest.raises(sysop.SysopError, match="does not exist"):
        sysop.apply_acl(None)
    grant.assert_not_called()
