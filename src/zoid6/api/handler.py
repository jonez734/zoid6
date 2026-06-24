#!/usr/bin/env python3
# zoid6/api/handler.py
# Unified MessageRouter for zoid6 - loads all BBS modules

from typing import Any, Dict, Optional
import json
from pathlib import Path


class MessageRouter:
    """Unified MessageRouter that loads all enabled BBS module routers."""

    def __init__(self, args: Any) -> None:
        self.args = args
        self.config = self._load_config()
        self.routers: Dict[str, Any] = {}

    def _load_config(self) -> Dict[str, Any]:
        """Load zoid6 configuration from bed.json."""
        config_path = Path(__file__).parent.parent / "data" / "bed.json"
        if config_path.exists():
            with open(config_path) as f:
                return json.load(f)
        return {}

    def register_all(self, server: Any) -> None:
        """Register all enabled module services with the WebSocket server."""
        services_config = self.config.get("services", {})

        for module_name, module_config in services_config.items():
            if not isinstance(module_config, dict):
                continue

            enabled = module_config.get("enabled", False)
            if not enabled:
                continue

            self._register_module(module_name, module_config, server)

    def _register_module(self, module_name: str, module_config: Dict[str, Any], server: Any) -> None:
        """Register a single module's services."""
        try:
            if module_name == "casino":
                self._register_casino(module_config, server)
            elif module_name == "empyre":
                self._register_empyre(module_config, server)
            elif module_name == "murdermotel":
                self._register_murdermotel(module_config, server)
            elif module_name == "mistermcfeely":
                self._register_mistermcfeely(module_config, server)
            else:
                print(f"Unknown module: {module_name}")
        except ImportError as e:
            print(f"Failed to import {module_name}: {e}")
        except Exception as e:
            print(f"Failed to register {module_name}: {e}")

    def _register_casino(self, config: Dict[str, Any], server: Any) -> None:
        """Register casino module services."""
        from casino.api.handler import MessageRouter as CasinoRouter

        router = CasinoRouter(self.args)
        router.register_all(server)
        self.routers["casino"] = router

    def _register_empyre(self, config: Dict[str, Any], server: Any) -> None:
        """Register empyre module services."""
        from empyre.api.handler import MessageRouter as EmpyreRouter

        router = EmpyreRouter(self.args)
        router.register_all(server)
        self.routers["empyre"] = router

    def _register_murdermotel(self, config: Dict[str, Any], server: Any) -> None:
        """Register murdermotel module services."""
        from murdermotel.api.handler import MessageRouter as MurderMotelRouter

        router = MurderMotelRouter(self.args)
        router.register_all(server)
        self.routers["murdermotel"] = router

    def _register_mistermcfeely(self, config: Dict[str, Any], server: Any) -> None:
        """Register mistermcfeely (postoffice) module services."""
        from mistermcfeely.api.handler import MessageRouter as MisterMcFeelyRouter

        router = MisterMcFeelyRouter(self.args)
        router.register_all(server)
        self.routers["mistermcfeely"] = router


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
