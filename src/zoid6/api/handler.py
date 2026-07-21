#!/usr/bin/env python3
# zoid6/api/handler.py
# Unified MessageRouter for zoid6 - loads all BBS modules

from typing import Any, Dict, Optional
from bbsengine6 import io, module as bbsmodule
import json
from pathlib import Path

from zoid6.api.monikerrouter import MonikerAuthRouter  # noqa: F401


class MessageRouter:
    """Unified MessageRouter that loads all enabled BBS module routers."""

    def __init__(self, args: Any) -> None:
        self.args = args
        self.config = self._load_config()
        self.routers: Dict[str, Any] = {}

    def _load_config(self) -> Dict[str, Any]:
        """Load zoid6 configuration from bed.json."""
        config_path = Path(__file__).parent.parent / "data" / "bed.json"
        io.echo(f"bed.json config path: {config_path}", level="info")
        if config_path.exists():
            with open(config_path) as f:
                return json.load(f)
        return {}

    def register_all(self, server: Any) -> None:
        """Register all enabled module services with the WebSocket server.

        The built-in `list_services` handler lives on
        `bbsengine6.net.WebSocketServer` itself (it has access to
        the live `_services` dict, so a per-router copy is duplicate
        state). Routers only need to register their own message types
        here.

        Raises RuntimeError if bed's AuthService is not already
        registered (auth is handled by bed natively, not by this
        router) or if the member module failed to load.
        """
        if server.get_service("auth") is None:
            raise RuntimeError(
                "AuthService not registered on server. "
                "bed must start with auth enabled (token_persistence != 'none') "
                "before the zoid6 router loads."
            )

        services_config = self.config.get("services", {})

        for module_name, module_config in services_config.items():
            if not isinstance(module_config, dict):
                continue

            enabled = module_config.get("enabled", False)
            if not enabled:
                continue

            self._register_module(module_name, module_config, server)

        if server.get_service("member_profile") is None:
            raise RuntimeError(
                "Member service not registered. "
                "bbsengine6.member.api.handler must be importable and "
                "enabled in bed.json for zoid6 to start."
            )

    def _register_module(self, module_name: str, module_config: Dict[str, Any], server: Any) -> None:
        """Register a single module's services."""
        module_path = module_config.get("modulepath")
        if not module_path:
            print(f"No modulepath configured for {module_name}")
            return

        try:
            module = bbsmodule.load(self.args, module_path)
            RouterClass = getattr(module, "MessageRouter")
            router = RouterClass(self.args)
            router.register_all(server)
            self.routers[module_name] = router
        except ImportError as e:
            print(f"Failed to import {module_name}: {e}")
        except AttributeError as e:
            print(f"MessageRouter not found in {module_path}: {e}")
        except Exception as e:
            print(f"Failed to register {module_name}: {e}")

def get_config() -> Dict[str, Any]:
    """Get zoid6 configuration."""
    config_path = Path(__file__).parent.parent / "data" / "bed.json"
    if config_path.exists():
        with open(config_path) as f:
            return json.load(f)
    return {}


def get_enabled_services() -> list[str]:
    """Get list of enabled service names."""
    config = get_config()
    services = config.get("services", {})
    return [name for name, cfg in services.items() if isinstance(cfg, dict) and cfg.get("enabled")]
