#!/usr/bin/env python3
# zoid6/api/monikerrouter.py
# MonikerAuthRouter - Example router. Authentication is delegated to
# bed's MonikerOnlyCredentialProvider so the password-validating
# behaviour lives in one place. Bed's AuthService handles the wire
# ``auth`` message natively, so this router registers only ``ping``
# with the WebSocket server.
#
# Games that want a custom ``auth`` flow can subclass this router,
# override ``register_all`` to register their own ``auth`` handler, and
# call ``self._handle_auth`` (or a customised version) from there.

from typing import Any, Dict, Optional

from bbsengine6 import io
from bed.api.credential_provider import (
    CredentialProvider,
    MonikerOnlyCredentialProvider,
)
from bed.api.token_store import MemberInfo


class MonikerAuthRouter:
    """Example router: delegates auth to bed's
    :class:`MonikerOnlyCredentialProvider` and registers ``ping``.

    The router is kept around as a worked example for games that want
    to plug their own message handlers into the bbsengine6 WebSocket
    server. Auth itself is the responsibility of bed's
    :class:`bed.api.auth.AuthService`; this router therefore registers
    only ``ping`` by default.
    """

    def __init__(
        self,
        args: Any,
        *,
        provider: Optional[CredentialProvider] = None,
    ):
        self.args = args
        self.sessions: Dict[int, Dict[str, Any]] = {}
        self._provider: CredentialProvider = (
            provider if provider is not None else MonikerOnlyCredentialProvider()
        )

    def register_all(self, server: Any) -> None:
        server.register_service(self, ["ping"])

    async def handle_message(
        self, server: Any, websocket: Any, path: str, message: Dict[str, Any]
    ) -> Optional[Dict[str, Any]]:
        """Handle incoming message."""
        msg_type = message.get("type")

        if msg_type == "ping":
            return await self._handle_ping()

        return None

    async def _handle_auth(
        self, websocket: Any, message: Dict[str, Any]
    ) -> Dict[str, Any]:
        """Delegate authentication to bed's
        :class:`MonikerOnlyCredentialProvider`.

        Preserves the historical wire-level distinctions
        ("Moniker required" / "Invalid moniker" / "Authentication
        service unavailable" / "Member not found") by mapping
        provider outcomes back to the original envelopes. This method
        is NOT routed by :meth:`register_all` because bed's
        AuthService already handles ``auth``; subclasses can opt in
        by overriding ``register_all``.
        """
        moniker = (message.get("moniker") or "").strip()
        password = message.get("password", "")

        if not moniker:
            return {
                "type": "auth_result",
                "success": False,
                "moniker": moniker,
                "balance": 0,
                "message": "Moniker required",
            }

        try:
            info: Optional[MemberInfo] = self._provider.authenticate(
                self.args,
                moniker,
                password,
                pool=getattr(self.args, "pool", None),
            )
        except ValueError as e:
            io.echo(
                f"MonikerAuthRouter: invalid moniker {moniker!r}: {e}",
                level="warning",
            )
            return {
                "type": "auth_result",
                "success": False,
                "moniker": moniker,
                "balance": 0,
                "message": "Invalid moniker",
            }
        except Exception as e:
            io.echo(
                f"MonikerAuthRouter: DB error checking {moniker!r}: {e}",
                level="error",
            )
            return {
                "type": "auth_result",
                "success": False,
                "moniker": moniker,
                "balance": 0,
                "message": "Authentication service unavailable",
            }

        if info is None:
            return {
                "type": "auth_result",
                "success": False,
                "moniker": moniker,
                "balance": 0,
                "message": "Member not found",
            }

        self.sessions[id(websocket)] = {
            "moniker": info.moniker,
            "is_sysop": info.is_sysop,
        }

        return {
            "type": "auth_result",
            "success": True,
            "moniker": moniker,
            "balance": 0,
            "message": "Authenticated (moniker check only)",
        }

    async def _handle_ping(self) -> Dict[str, Any]:
        """Handle ping message."""
        return {"type": "pong"}
