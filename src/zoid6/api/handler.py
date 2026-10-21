#!/usr/bin/env python3
# zoid6/api/handler.py
# Unified MessageRouter for zoid6 - loads all BBS modules

from typing import Any, Dict
from bbsengine6 import io, module as bbsmodule
import json

from zoid6.api.monikerrouter import MonikerAuthRouter  # noqa: F401
from zoid6.data import get as data_get, path as data_path


class MessageRouter:
    """Unified MessageRouter that loads all enabled BBS module routers.

    Optional ``session_registry``, ``secret``, ``token_store``, and
    ``instance_id`` kwargs mirror the casino ``MessageRouter`` wiring
    and are forwarded by ``bed.main.BED.start`` when auth is enabled.
    zoid6 itself does not consume them (sub-router constructors are
    called with just ``self.args``), but accepting them keeps the
    router compatible with the bed startup contract and prevents the
    ``unexpected keyword argument 'session_registry'`` TypeError.
    """

    def __init__(
        self,
        args: Any,
        *,
        session_registry: Any = None,
        secret: Any = None,
        token_store: Any = None,
        instance_id: Any = None,
        clock: Any = None,
        channel_state: Any = None,
    ) -> None:
        self.args = args
        self.session_registry = session_registry
        self.secret = secret
        self.token_store = token_store
        self.instance_id = instance_id
        self.clock = clock
        # bed's shared ChannelState. Kept so the router shares bed's channel
        # map rather than building its own.
        self.channel_state = channel_state
        self.config = self._load_config()
        self.routers: Dict[str, Any] = {}

    def _load_config(self) -> Dict[str, Any]:
        """Load configuration via bed.config.load_config().

        Delegates to bed's canonical loader which handles env-var
        merging (BED_* prefix) and override support.  Requires
        args.config_file (the --config path) — no fallback search.
        Validates the resulting shape so misconfiguration fails loudly
        at startup rather than silently skipping modules.
        """
        from bed.config import load_config

        config_path = getattr(self.args, "config_file", None)
        if not config_path:
            raise RuntimeError(
                "MessageRouter requires args.config_file (the --config path)."
            )
        config = load_config(config_path)
        self._validate_config(config)
        return config

    @staticmethod
    def _validate_config(config: Any) -> None:
        """Raise RuntimeError if ``config`` is not a dict of section→dict.

        The router iterates ``config["services"]`` later; a non-dict
        top-level or a missing/typed ``services`` key is a config bug,
        not a runtime condition we should silently paper over.
        """
        if not isinstance(config, dict):
            raise RuntimeError(
                f"MessageRouter config must be a JSON object, got {type(config).__name__}"
            )
        services = config.get("services")
        if services is None:
            return
        if not isinstance(services, dict):
            raise RuntimeError(
                f"MessageRouter config 'services' must be an object, got {type(services).__name__}"
            )
        for module_name, module_config in services.items():
            if not isinstance(module_config, dict):
                raise RuntimeError(
                    f"MessageRouter config services.{module_name} must be an object, "
                    f"got {type(module_config).__name__}"
                )

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
                "enabled in zoid6.json for zoid6 to start."
            )

    def _register_module(self, module_name: str, module_config: Dict[str, Any], server: Any) -> None:
        """Register a single module's services.

        Forwards auth wiring (``session_registry``, ``secret``,
        ``token_store``, ``instance_id``, ``clock``) to each sub-router
        so handlers like casino's ``MessageRouter`` can wire their
        ``AuthService`` to BED's :class:`SessionRegistry` rather than
        falling back to a fresh in-memory :class:`CasinoSessionManager`
        that cannot see sessions created by ``bed.api.auth.AuthService``.
        Falls back to the no-kwargs call if a sub-router rejects the
        kwargs (legacy routers that only take ``args``).

        Failures (ImportError, missing MessageRouter class, register_all
        raising) are logged at error level and the module is skipped.
        register_all() continues with the remaining services so the
        daemon can still answer list_services and any other services
        that did load. The ``required`` flag in zoid6.json is accepted
        for documentation but no longer changes behavior — every
        failure is treated as non-fatal here.
        """
        module_path = module_config.get("modulepath")
        if not module_path:
            io.echo(
                f"MessageRouter: no modulepath configured for {module_name}",
                level="warning",
            )
            return

        sub_router_kwargs = dict(
            session_registry=self.session_registry,
            secret=self.secret,
            token_store=self.token_store,
            instance_id=self.instance_id,
            clock=self.clock,
            # Forward the module's config subtree so routers that care
            # about per-module settings (e.g. the channel auto_seed
            # list, admin_handler flag) can read it without re-parsing
            # the whole JSON. Routers that don't take ``config`` are
            # unaffected because the try/except TypeError fallback below
            # drops back to the bare ``RouterClass(self.args)`` call.
            config=module_config,
        )

        try:
            module = bbsmodule.load(self.args, module_path)
            RouterClass = getattr(module, "MessageRouter")
            try:
                router = RouterClass(self.args, **sub_router_kwargs)
            except TypeError:
                # Router's __init__ doesn't accept one or more of the
                # kwargs (typically an older router missing ``config``);
                # fall back to the bare call.
                router = RouterClass(self.args)
            router.register_all(server)
            self.routers[module_name] = router
        except ImportError as e:
            io.echo(
                f"MessageRouter: failed to import {module_name}: {e}",
                level="error",
            )
        except AttributeError as e:
            io.echo(
                f"MessageRouter: MessageRouter not found in {module_path}: {e}",
                level="error",
            )
        except Exception as e:
            io.echo(
                f"MessageRouter: failed to register {module_name}: {e}",
                level="error",
            )

def get_config() -> Dict[str, Any]:
    """Read the bundled zoid6/data/zoid6.json."""
    with data_get("zoid6.json") as f:
        return json.load(f)


def get_bundled_config_path() -> str:
    """Resolve the packaged zoid6.json filesystem path."""
    try:
        p = bbsmodule.file("zoid6", "data", "zoid6.json")
        if p is not None:
            return str(p)
    except TypeError:
        pass
    return data_path("zoid6.json")


def get_enabled_services() -> list[str]:
    """Get list of enabled service names."""
    config = get_config()
    services = config.get("services", {})
    return [name for name, cfg in services.items() if isinstance(cfg, dict) and cfg.get("enabled")]
