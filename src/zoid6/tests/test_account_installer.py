"""The account installer (scripts/INSTALL-zoid6-venv.sh) is tracked, and it clears pip's leftovers.

A failed uninstall leaves a '~<name>' directory in site-packages; pip then warns on every run.
The installer removes those before it installs, and only those.
"""

import pathlib
import shutil
import subprocess

import pytest

ROOT = pathlib.Path(__file__).resolve().parents[3]
INSTALLER = ROOT / "scripts" / "INSTALL-zoid6-venv.sh"


@pytest.mark.unit
def test_installer_is_a_valid_posix_shell_script():
    assert shutil.which("sh")
    proc = subprocess.run(["sh", "-n", str(INSTALLER)], capture_output=True, text=True)
    assert proc.returncode == 0, proc.stderr


@pytest.mark.unit
def test_installer_clears_only_pip_leftovers(tmp_path):
    # Reproduce the cleanup loop on a fake venv: '~'-prefixed entries go, real packages stay.
    venv = tmp_path / "venv"
    sp = venv / "lib64" / "python3.12" / "site-packages"
    (sp / "casino").mkdir(parents=True)
    (sp / "~asino-0.4.0.dev1.dist-info").mkdir()
    (sp / "~asino").mkdir()
    (sp / "bed.pth").write_text("")
    text = INSTALLER.read_text()
    assert 'for stale in "$sp"/~*' in text, "the installer must remove pip's ~ leftovers"
    # Run only the cleanup block, extracted from the installer.
    block = text[text.index("for sp in"):text.index('"$VENV/bin/python" -m pip install --upgrade pip')]
    subprocess.run(["sh", "-c", f'VENV="$1"\n{block}', "sh", str(venv)], check=True)
    assert not (sp / "~asino").exists()
    assert not (sp / "~asino-0.4.0.dev1.dist-info").exists()
    assert (sp / "casino").is_dir()
    assert (sp / "bed.pth").is_file()
