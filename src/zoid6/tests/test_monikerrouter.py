#!/usr/bin/env python3
"""Tests for zoid6.api.monikerrouter.MonikerAuthRouter.

The router is an example that delegates authentication to bed's
MonikerOnlyCredentialProvider. Bed handles the wire ``auth`` message
natively, so this router only registers ``ping`` with the WebSocket
server. The ``_handle_auth`` method is kept as a documented private
helper for subclasses that want to opt into routing ``auth`` themselves.

These tests use mocks to avoid touching a real PostgreSQL database.
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

    def test_register_all_registers_ping_only(
        self, router_args, mock_server
    ):
        """register_all registers ``ping`` with the server.

        ``auth`` is NOT registered here; bed's AuthService handles the
        wire ``auth`` message natively. The router must not overwrite
        bed's auth handler.
        """
        from zoid6.api.monikerrouter import MonikerAuthRouter

        router = MonikerAuthRouter(router_args)
        router.register_all(mock_server)

        mock_server.register_service.assert_called_once()
        args, _ = mock_server.register_service.call_args
        assert args[0] is router
        assert args[1] == ["ping"]

    def test_handle_message_ping_dispatches_to_ping_handler(
        self, router_args, mock_server
    ):
        """handle_message routes ``ping`` to ``_handle_ping``."""
        from zoid6.api.monikerrouter import MonikerAuthRouter

        router = MonikerAuthRouter(router_args)
        with patch.object(router, "_handle_ping", new=AsyncMock()) as mock_ping:
            mock_ping.return_value = {"type": "pong"}
            result = asyncio.run(
                router.handle_message(mock_server, MagicMock(), "/", {"type": "ping"})
            )
        mock_ping.assert_awaited_once()
        assert result == {"type": "pong"}

    def test_handle_message_unknown_type_returns_none(
        self, router_args, mock_server
    ):
        """Unknown message types return None and don't call handlers.

        ``auth`` is no longer routed by this router; ``list_services``
        is answered directly by ``WebSocketServer.dispatch_message``.
        Anything else is a real unknown.
        """
        from zoid6.api.monikerrouter import MonikerAuthRouter

        router = MonikerAuthRouter(router_args)
        with patch.object(router, "_handle_ping", new=AsyncMock()) as mock_ping:
            result = asyncio.run(
                router.handle_message(mock_server, MagicMock(), "/", {"type": "wat"})
            )
        assert result is None
        mock_ping.assert_not_awaited()


class TestMonikerAuthRouterPing:
    """Plain response handlers."""

    def test_ping_returns_pong(self, router_args):
        from zoid6.api.monikerrouter import MonikerAuthRouter

        router = MonikerAuthRouter(router_args)
        assert asyncio.run(router._handle_ping()) == {"type": "pong"}


class TestMonikerAuthRouterHandleAuthProvider:
    """``_handle_auth`` delegates to bed's MonikerOnlyCredentialProvider."""

    def _build_router_with_provider(self, router_args, provider):
        from zoid6.api.monikerrouter import MonikerAuthRouter

        return MonikerAuthRouter(router_args, provider=provider)

    def test_empty_moniker_rejects_without_provider_call(
        self, router_args
    ):
        """An empty moniker is rejected with 'Moniker required' and no
        provider call."""
        from bed.api.token_store import MemberInfo

        fake_provider = MagicMock()
        fake_provider.authenticate = MagicMock(
            return_value=MemberInfo(moniker="x", is_sysop=False, balance=None)
        )
        router = self._build_router_with_provider(router_args, fake_provider)
        ws = MagicMock()

        result = asyncio.run(
            router._handle_auth(ws, {"type": "auth", "moniker": "", "password": "x"})
        )

        assert result == {
            "type": "auth_result",
            "success": False,
            "moniker": "",
            "balance": 0,
            "message": "Moniker required",
        }
        fake_provider.authenticate.assert_not_called()
        assert id(ws) not in router.sessions

    def test_provider_returns_member_info_registers_session(
        self, router_args
    ):
        """A successful provider lookup registers the session with
        is_sysop from MemberInfo."""
        from bed.api.token_store import MemberInfo

        fake_provider = MagicMock()
        fake_provider.authenticate = MagicMock(
            return_value=MemberInfo(moniker="alice", is_sysop=True, balance=None)
        )
        router = self._build_router_with_provider(router_args, fake_provider)
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
        assert router.sessions[id(ws)]["is_sysop"] is True

        # Verify the provider was called with the router args + pool.
        fake_provider.authenticate.assert_called_once()
        kwargs = fake_provider.authenticate.call_args.kwargs
        assert kwargs["pool"] is router_args.pool

    def test_provider_returns_none_means_member_not_found(
        self, router_args
    ):
        """Provider returning None -> 'Member not found'."""
        fake_provider = MagicMock()
        fake_provider.authenticate = MagicMock(return_value=None)
        router = self._build_router_with_provider(router_args, fake_provider)
        ws = MagicMock()

        result = asyncio.run(
            router._handle_auth(ws, {"type": "auth", "moniker": "ghost", "password": "x"})
        )

        assert result["success"] is False
        assert result["moniker"] == "ghost"
        assert result["message"] == "Member not found"
        assert id(ws) not in router.sessions

    def test_provider_value_error_means_invalid_moniker(
        self, router_args
    ):
        """Provider raising ValueError -> 'Invalid moniker'."""
        fake_provider = MagicMock()
        fake_provider.authenticate = MagicMock(
            side_effect=ValueError("contains non-ASCII characters")
        )
        router = self._build_router_with_provider(router_args, fake_provider)
        ws = MagicMock()

        result = asyncio.run(
            router._handle_auth(ws, {"type": "auth", "moniker": "café", "password": "x"})
        )

        assert result["success"] is False
        assert result["message"] == "Invalid moniker"
        assert result["moniker"] == "café"
        assert id(ws) not in router.sessions

    def test_provider_generic_exception_means_service_unavailable(
        self, router_args
    ):
        """Any non-ValueError exception from the provider -> 'service
        unavailable'."""
        fake_provider = MagicMock()
        fake_provider.authenticate = MagicMock(
            side_effect=RuntimeError("connection refused")
        )
        router = self._build_router_with_provider(router_args, fake_provider)
        ws = MagicMock()

        result = asyncio.run(
            router._handle_auth(ws, {"type": "auth", "moniker": "alice", "password": "x"})
        )

        assert result["success"] is False
        assert result["message"] == "Authentication service unavailable"
        assert id(ws) not in router.sessions

    def test_default_provider_is_moniker_only(self, router_args):
        """No provider passed -> default to MonikerOnlyCredentialProvider."""
        from zoid6.api.monikerrouter import MonikerAuthRouter
        from bed.api.credential_provider import MonikerOnlyCredentialProvider

        router = MonikerAuthRouter(router_args)
        assert isinstance(router._provider, MonikerOnlyCredentialProvider)


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
