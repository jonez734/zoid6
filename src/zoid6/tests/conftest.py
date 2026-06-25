"""Shared pytest fixtures for zoid6 tests."""
import argparse
import sys
from unittest.mock import MagicMock

import pytest

sys.path.insert(0, "/home/opencode/data/work/zoid6/src")


@pytest.fixture
def mock_args():
    """Provide a MagicMock args namespace for tests."""
    args = MagicMock()
    args.debug = False
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
        databasename="test",
        databasehost="localhost",
        databaseport=5432,
        databaseuser="test",
        databasepassword="test",
    )


@pytest.fixture
def mock_server():
    """Provide a MagicMock WebSocket server."""
    server = MagicMock()
    server.register_service = MagicMock()
    server.list_services = MagicMock(return_value={})
    server.get_service = MagicMock(return_value=None)
    return server
