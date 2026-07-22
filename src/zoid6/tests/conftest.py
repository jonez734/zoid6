"""Shared pytest fixtures for zoid6 tests."""
import argparse
import sys
from pathlib import Path
from unittest.mock import MagicMock

import pytest

sys.path.insert(0, "/home/opencode/data/work/zoid6/src")

_BED_JSON = str(Path(__file__).resolve().parent.parent / "data" / "bed.json")


@pytest.fixture
def mock_args():
    """Provide a MagicMock args namespace for tests."""
    args = MagicMock()
    args.debug = False
    args.config_file = _BED_JSON
    args.databasename = "test"
    args.databasehost = "localhost"
    args.databaseport = 5432
    args.databaseuser = "test"
    args.databasepassword = "test"
    return args


@pytest.fixture
def real_args():
    """Provide a real argparse.Namespace for tests."""
    return argparse.Namespace(
        debug=False,
        config_file=_BED_JSON,
        databasename="test",
        databasehost="localhost",
        databaseport=5432,
        databaseuser="test",
        databasepassword="test",
    )


@pytest.fixture
def mock_server():
    """Provide a MagicMock WebSocket server.

    By default get_service returns a mock for the services that bed
    and zoid6 guarantee at runtime ('auth' from bed, 'member_profile'
    from the bbsengine6 member module).  Tests that want to exercise
    the startup-check failure paths can override get_service.
    """
    server = MagicMock()
    server.register_service = MagicMock()
    server.list_services = MagicMock(return_value={})

    _PRESENT = {"auth", "member_profile"}

    def _get_service(name):
        if name in _PRESENT:
            return MagicMock()
        return None

    server.get_service = MagicMock(side_effect=_get_service)
    return server
