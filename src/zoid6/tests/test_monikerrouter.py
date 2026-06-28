#!/usr/bin/env python3
"""Tests for zoid6.api.monikerrouter.MonikerAuthRouter.

The router is an example that authenticates a member by confirming the
supplied moniker exists in the database. The password is NOT validated.
These tests use mocks for the bbsengine6.member helpers to avoid touching
a real PostgreSQL database.
"""
import sys
from unittest.mock import MagicMock, patch

import pytest

sys.path.insert(0, "/home/opencode/data/work/zoid6/src")


@pytest.fixture
def router_args(mock_args):
    """Provide args with a pool attribute for MonikerAuthRouter tests."""
    mock_args.pool = MagicMock()
    return mock_args


class TestMonikerAuthRouterRegistration:
    """register_all and message dispatch."""

    def test_register_all_registers_expected_services(
        self, router_args, mock_server
    ):
        """register_all registers auth, ping, list_services with the server."""
        from zoid6.api.monikerrouter import MonikerAuthRouter

        router = MonikerAuthRouter(router_args)
        router.register_all(mock_server)

        mock_server.register_service.assert_called_once()
        args, _ = mock_server.register_service.call_args
        assert args[0] is router
        assert args[1] == ["auth", "ping", "list_services"]

    @pytest.mark.parametrize("msg_type", ["auth", "ping", "list_services"])
    def test_handle_message_dispatches_known_types(
        self, router_args, mock_server, msg_type
    ):
        """handle_message routes known types to their handlers."""
        from zoid6.api.monikerrouter import MonikerAuthRouter

        router = MonikerAuthRouter(router_args)
        with patch.object(router, "_handle_auth", new=AsyncMock()) as mock_auth, \
             patch.object(router, "_handle_ping", new=AsyncMock()) as mock_ping, \
             patch.object(router, "_handle_list_services", new=AsyncMock()) as mock_list:
            mock_auth.return_value = {"type": "auth_result"}
            mock_ping.return_value = {"type": "pong"}
            mock_list.return_value = {"type": "services"}

            result = asyncio.run(
                router.handle_message(mock_server, MagicMock(), "/", {"type": msg_type})
            )

        if msg_type == "auth":
            mock_auth.assert_awaited_once()
            assert result == {"type": "auth_result"}
        elif msg_type == "ping":
            mock_ping.assert_awaited_once()
            assert result == {"type": "pong"}
        elif msg_type == "list_services":
            mock_list.assert_awaited_once()
            assert result == {"type": "services"}

    def test_handle_message_unknown_type_returns_none(self, router_args, mock_server):
        """Unknown message types return None and don't call handlers."""
        from zoid6.api.monikerrouter import MonikerAuthRouter

        router = MonikerAuthRouter(router_args)
        with patch.object(router, "_handle_auth", new=AsyncMock()) as mock_auth, \
             patch.object(router, "_handle_ping", new=AsyncMock()) as mock_ping, \
             patch.object(router, "_handle_list_services", new=AsyncMock()) as mock_list:
            result = asyncio.run(
                router.handle_message(mock_server, MagicMock(), "/", {"type": "wat"})
            )

        assert result is None
        mock_auth.assert_not_awaited()
        mock_ping.assert_not_awaited()
        mock_list.assert_not_awaited()


class TestMonikerAuthRouterPingAndList:
    """Plain response handlers."""

    def test_ping_returns_pong(self, router_args):
        from zoid6.api.monikerrouter import MonikerAuthRouter

        router = MonikerAuthRouter(router_args)
        assert asyncio.run(router._handle_ping()) == {"type": "pong"}

    def test_list_services_returns_services(self, router_args):
        from zoid6.api.monikerrouter import MonikerAuthRouter

        router = MonikerAuthRouter(router_args)
        result = asyncio.run(router._handle_list_services())
        assert result == {
            "type": "services",
            "services": ["auth", "ping", "list_services"],
        }


class TestMonikerAuthRouterHandleAuthEmpty:
    """Empty / missing moniker short-circuits before any DB call."""

    def test_empty_moniker_rejects_without_db_call(
        self, router_args, monkeypatch
    ):
        """An empty moniker is rejected with 'Moniker required' and no DB call."""
        from zoid6.api import monikerrouter

        calls = {"moniker_exists": 0, "issysop": 0}
        fake_exists = MagicMock(side_effect=lambda *a, **kw: calls.__setitem__("moniker_exists", calls["moniker_exists"] + 1) or True)
        fake_issysop = MagicMock(side_effect=lambda *a, **kw: calls.__setitem__("issysop", calls["issysop"] + 1) or False)
        monkeypatch.setattr(monikerrouter.member, "moniker_exists", fake_exists)
        monkeypatch.setattr(monikerrouter.member, "issysop", fake_issysop)

        router = monikerrouter.MonikerAuthRouter(router_args)
        ws = MagicMock()
        result = asyncio.run(router._handle_auth(ws, {"type": "auth", "moniker": "", "password": "x"}))

        assert result == {
            "type": "auth_result",
            "success": False,
            "moniker": "",
            "balance": 0,
            "message": "Moniker required",
        }
        assert calls["moniker_exists"] == 0
        assert calls["issysop"] == 0
        assert id(ws) not in router.sessions

    def test_missing_moniker_key_also_rejects(self, router_args, monkeypatch):
        """A message with no 'moniker' key is treated as empty."""
        from zoid6.api import monikerrouter

        fake_exists = MagicMock(return_value=True)
        monkeypatch.setattr(monikerrouter.member, "moniker_exists", fake_exists)

        router = monikerrouter.MonikerAuthRouter(router_args)
        ws = MagicMock()
        result = asyncio.run(router._handle_auth(ws, {"type": "auth", "password": "x"}))

        assert result["success"] is False
        assert result["message"] == "Moniker required"
        fake_exists.assert_not_called()


class TestMonikerAuthRouterHandleAuthSuccess:
    """Moniker exists -> success path."""

    def test_moniker_exists_true_registers_session(self, router_args, monkeypatch):
        from zoid6.api import monikerrouter

        monkeypatch.setattr(monikerrouter.member, "moniker_exists", MagicMock(return_value=True))
        monkeypatch.setattr(monikerrouter.member, "issysop", MagicMock(return_value=False))

        router = monikerrouter.MonikerAuthRouter(router_args)
        ws = MagicMock()
        result = asyncio.run(
            router._handle_auth(ws, {"type": "auth", "moniker": "alice", "password": ""})
        )

        assert result["success"] is True
        assert result["moniker"] == "alice"
        assert result["balance"] == 0
        assert "moniker check" in result["message"]
        assert id(ws) in router.sessions
        assert router.sessions[id(ws)]["moniker"] == "alice"
        assert router.sessions[id(ws)]["is_sysop"] is False

    def test_issysop_true_sets_is_sysop(self, router_args, monkeypatch):
        """A sysop member is registered with is_sysop=True."""
        from zoid6.api import monikerrouter

        monkeypatch.setattr(monikerrouter.member, "moniker_exists", MagicMock(return_value=True))
        monkeypatch.setattr(monikerrouter.member, "issysop", MagicMock(return_value=True))

        router = monikerrouter.MonikerAuthRouter(router_args)
        ws = MagicMock()
        asyncio.run(router._handle_auth(ws, {"type": "auth", "moniker": "root", "password": "x"}))

        assert router.sessions[id(ws)]["is_sysop"] is True

    def test_issysop_none_collapsed_to_false(self, router_args, monkeypatch):
        """issysop returning None (DB error) is treated as is_sysop=False."""
        from zoid6.api import monikerrouter

        monkeypatch.setattr(monikerrouter.member, "moniker_exists", MagicMock(return_value=True))
        monkeypatch.setattr(monikerrouter.member, "issysop", MagicMock(return_value=None))

        router = monikerrouter.MonikerAuthRouter(router_args)
        ws = MagicMock()
        asyncio.run(router._handle_auth(ws, {"type": "auth", "moniker": "alice", "password": "x"}))

        assert router.sessions[id(ws)]["is_sysop"] is False

    @pytest.mark.parametrize("password", ["", "wrong", "hunter2"])
    def test_password_is_ignored_when_moniker_exists(
        self, router_args, monkeypatch, password
    ):
        """Any password is accepted as long as the moniker exists."""
        from zoid6.api import monikerrouter

        monkeypatch.setattr(monikerrouter.member, "moniker_exists", MagicMock(return_value=True))
        monkeypatch.setattr(monikerrouter.member, "issysop", MagicMock(return_value=False))

        router = monikerrouter.MonikerAuthRouter(router_args)
        ws = MagicMock()
        result = asyncio.run(
            router._handle_auth(ws, {"type": "auth", "moniker": "alice", "password": password})
        )

        assert result["success"] is True


class TestMonikerAuthRouterHandleAuthRejection:
    """Unknown / invalid moniker paths."""

    def test_moniker_exists_false_rejects(self, router_args, monkeypatch):
        from zoid6.api import monikerrouter

        monkeypatch.setattr(monikerrouter.member, "moniker_exists", MagicMock(return_value=False))
        fake_issysop = MagicMock(return_value=False)
        monkeypatch.setattr(monikerrouter.member, "issysop", fake_issysop)

        router = monikerrouter.MonikerAuthRouter(router_args)
        ws = MagicMock()
        result = asyncio.run(
            router._handle_auth(ws, {"type": "auth", "moniker": "ghost", "password": "x"})
        )

        assert result == {
            "type": "auth_result",
            "success": False,
            "moniker": "ghost",
            "balance": 0,
            "message": "Member not found",
        }
        assert id(ws) not in router.sessions
        fake_issysop.assert_not_called()

    def test_moniker_exists_value_error_rejects_as_invalid(
        self, router_args, monkeypatch
    ):
        """ValueError from moniker_exists -> 'Invalid moniker' failure."""
        from zoid6.api import monikerrouter

        def boom(*args, **kwargs):
            raise ValueError("contains non-ASCII characters")

        monkeypatch.setattr(monikerrouter.member, "moniker_exists", MagicMock(side_effect=boom))
        fake_issysop = MagicMock(return_value=False)
        monkeypatch.setattr(monikerrouter.member, "issysop", fake_issysop)

        router = monikerrouter.MonikerAuthRouter(router_args)
        ws = MagicMock()
        result = asyncio.run(
            router._handle_auth(ws, {"type": "auth", "moniker": "café", "password": "x"})
        )

        assert result["success"] is False
        assert result["message"] == "Invalid moniker"
        assert result["moniker"] == "café"
        assert id(ws) not in router.sessions
        fake_issysop.assert_not_called()

    def test_db_error_rejects_as_unavailable(self, router_args, monkeypatch):
        """Generic DB error -> 'Authentication service unavailable'."""
        from zoid6.api import monikerrouter

        def boom(*args, **kwargs):
            raise RuntimeError("connection refused")

        monkeypatch.setattr(monikerrouter.member, "moniker_exists", MagicMock(side_effect=boom))
        fake_issysop = MagicMock(return_value=False)
        monkeypatch.setattr(monikerrouter.member, "issysop", fake_issysop)

        router = monikerrouter.MonikerAuthRouter(router_args)
        ws = MagicMock()
        result = asyncio.run(
            router._handle_auth(ws, {"type": "auth", "moniker": "alice", "password": "x"})
        )

        assert result["success"] is False
        assert result["message"] == "Authentication service unavailable"
        assert id(ws) not in router.sessions
        fake_issysop.assert_not_called()


class TestMonikerAuthRouterReexport:
    """The class is re-exported from zoid6.api.handler."""

    def test_re_export_from_handler(self):
        from zoid6.api.handler import MonikerAuthRouter as Reexported
        from zoid6.api.monikerrouter import MonikerAuthRouter as Direct

        assert Reexported is Direct


# --- helpers (imported lazily so file parses even if pytest is absent) ---
import asyncio  # noqa: E402


def AsyncMock(*args, **kwargs):
    """Lightweight async mock compatible with MagicMock's await protocol."""
    from unittest.mock import AsyncMock as _AsyncMock
    return _AsyncMock(*args, **kwargs)
