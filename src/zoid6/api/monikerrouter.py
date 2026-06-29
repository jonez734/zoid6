#!/usr/bin/env python3
# zoid6/api/monikerrouter.py
# MonikerAuthRouter - Example router that authenticates a member by
# confirming the supplied moniker exists in the database. The password
# is NOT validated - any password is accepted. Use this as a starting
# point when wiring a real game to BED, then layer in password checking.

from typing import Any, Dict, Optional

from bbsengine6 import io, member


class MonikerAuthRouter:
    """Example router: confirms the moniker exists in the database.

    Compare with `bbsengine6.net.defaultrouter.DefaultRouter`, which
    accepts any moniker. This router refuses unknown / invalid / empty
    monikers but still accepts any password.
    """

    def __init__(self, args: Any):
        self.args = args
        self.sessions: Dict[int, Dict[str, Any]] = {}

    def register_all(self, server: Any) -> None:
        server.register_service(self, ["auth", "ping"])

    async def handle_message(
        self, server: Any, websocket: Any, path: str, message: Dict[str, Any]
    ) -> Optional[Dict[str, Any]]:
        """Handle incoming message."""
        msg_type = message.get("type")

        if msg_type == "auth":
            return await self._handle_auth(websocket, message)
        elif msg_type == "ping":
            return await self._handle_ping()

        return None

    async def _handle_auth(self, websocket: Any, message: Dict[str, Any]) -> Dict[str, Any]:
        """Handle auth message."""
        moniker = message.get("moniker", "")
        _password = message.get("password", "")

        if not moniker:
            return {
                "type": "auth_result",
                "success": False,
                "moniker": moniker,
                "balance": 0,
                "message": "Moniker required",
            }

        try:
            exists = member.moniker_exists(
                self.args, moniker, pool=self.args.pool
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

        if not exists:
            return {
                "type": "auth_result",
                "success": False,
                "moniker": moniker,
                "balance": 0,
                "message": "Member not found",
            }

        is_sysop = member.issysop(
            self.args, moniker=moniker, pool=self.args.pool
        )

        session_id = id(websocket)
        self.sessions[session_id] = {
            "moniker": moniker,
            "is_sysop": bool(is_sysop) if is_sysop is not None else False,
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
