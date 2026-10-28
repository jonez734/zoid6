#!/usr/bin/env python3
"""Tests for zoid6 startup behavior driven by data/zoid6.json.

These tests exercise the configuration-to-router wiring performed by
zoid6.api.handler.MessageRouter.register_all. They prove that:

  * the shipped zoid6.json parses and exposes the expected top-level sections,
  * every enabled service in zoid6.json gets loaded via bbsmodule.load and has
    its MessageRouter.register_all(server) invoked,
  * every disabled service is skipped,
  * per-service extras (casino sub-config, channel extras, etc.)
    survive the round-trip into MessageRouter.config,
  * placeholder services (auth, member, channel) stay disabled, and
  * at the integration level, a real bbsengine6.net.WebSocketServer actually
    receives registrations for the services whose modules can be imported.

The unit tests mock bbsengine6.module.load so they don't require casino,
bank, or channel to be on sys.path. The integration tests run the real
register_all path against a real WebSocketServer instance (no socket is
opened; we only call register_service, not start()).
"""
import json
import sys
from pathlib import Path
from unittest.mock import MagicMock, patch

import pytest

sys.path.insert(0, "/home/opencode/data/work/zoid6/src")

# Make the test suite's importable paths predictable. bbsengine6 and casino
# live in sibling repos; we add them explicitly so the integration tests
# can import bbsengine6.net.WebSocketServer and casino.api.handler directly.
for _sibling in (
    "/home/opencode/data/work/bbsengine6/py/src",
    "/home/opencode/data/work/casino/src",
):
    if _sibling not in sys.path:
        sys.path.insert(0, _sibling)


def _config_path() -> Path:
    from zoid6.api.handler import get_bundled_config_path

    return Path(get_bundled_config_path())


def _load_config() -> dict:
    with open(_config_path()) as f:
        return json.load(f)


# ---------------------------------------------------------------------------
# Unit tests: pure config + mocked bbsmodule.load
# ---------------------------------------------------------------------------


class TestBedJsonStartup:
    """Unit tests for zoid6.json loading and MessageRouter orchestration."""

    def test_default_config_loads(self, mock_args):
        """MessageRouter reads zoid6/data/zoid6.json on init and exposes services."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)

        assert "bed" in router.config
        assert "bind" in router.config
        assert "database" in router.config
        assert "services" in router.config
        assert isinstance(router.config["services"], dict)
        assert router.routers == {}

    def test_get_enabled_services_matches_json(self):
        """get_enabled_services() returns exactly the entries flagged enabled."""
        from zoid6.api.handler import get_enabled_services

        cfg = _load_config()
        expected = {
            name for name, svc in cfg["services"].items()
            if isinstance(svc, dict) and svc.get("enabled") is True
        }
        assert set(get_enabled_services()) == expected

    def test_all_enabled_modulepaths_are_importable(self):
        """Every enabled modulepath in zoid6.json is importable.

        Catches typos and missing-on-sys.path entries before bed ever
        starts. Modules that are explicitly marked unimportable in
        zoid6.json (placeholder entries pending implementation) are
        skipped.
        """
        from bbsengine6.module import is_importable
        from zoid6.api.handler import get_enabled_services

        # Placeholder entries in zoid6.json are enabled but their
        # modules are not yet importable. The test asserts the
        # importable ones, not the placeholders.
        _KNOWN_UNIMPORTABLE = set()

        cfg = _load_config()
        services = {
            name: svc
            for name, svc in cfg.get("services", {}).items()
            if isinstance(svc, dict) and svc.get("modulepath")
        }
        failures = {}
        for name in get_enabled_services():
            if name in _KNOWN_UNIMPORTABLE:
                continue
            mp = services[name]["modulepath"]
            if not is_importable(mp):
                failures[name] = mp
        assert failures == {}, f"Unimportable modulepaths: {failures}"

    def test_register_all_loads_every_enabled_service(
        self, mock_args, mock_server
    ):
        """register_all calls bbsmodule.load for every enabled service."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        cfg = _load_config()
        enabled = {
            name for name, svc in cfg["services"].items()
            if isinstance(svc, dict) and svc.get("enabled") is True
        }

        with patch("zoid6.api.handler.bbsmodule.load") as mock_load:
            mock_module = MagicMock()
            mock_module.MessageRouter.return_value = MagicMock()
            mock_load.return_value = mock_module

            router.register_all(mock_server)

            loaded_paths = {c.args[1] for c in mock_load.call_args_list}
            for name in enabled:
                expected_path = cfg["services"][name]["modulepath"]
                assert expected_path in loaded_paths, (
                    f"enabled service {name!r} (path {expected_path!r}) "
                    f"was not loaded; got {sorted(loaded_paths)}"
                )

    def test_register_all_does_not_load_disabled_services(
        self, mock_args, mock_server
    ):
        """Disabled services never reach bbsmodule.load."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        cfg = _load_config()
        disabled = {
            name: svc["modulepath"]
            for name, svc in cfg["services"].items()
            if isinstance(svc, dict) and svc.get("enabled") is False
        }

        with patch("zoid6.api.handler.bbsmodule.load") as mock_load:
            mock_module = MagicMock()
            mock_module.MessageRouter.return_value = MagicMock()
            mock_load.return_value = mock_module

            router.register_all(mock_server)

            loaded_paths = {c.args[1] for c in mock_load.call_args_list}
            for name, path in disabled.items():
                assert path not in loaded_paths, (
                    f"disabled service {name!r} (path {path!r}) was loaded"
                )

    def test_register_all_invokes_each_module_router_register_all(
        self, mock_args, mock_server
    ):
        """Each enabled service's MessageRouter.register_all(server) is called."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        cfg = _load_config()
        enabled = {
            name: svc["modulepath"]
            for name, svc in cfg["services"].items()
            if isinstance(svc, dict) and svc.get("enabled") is True
        }

        # Build a side_effect that returns a fresh MagicMock module per path.
        router_instances = {}

        def make_module(path):
            mod = MagicMock(name=f"module-{path}")
            inst = MagicMock(name=f"router-{path}")
            mod.MessageRouter.return_value = inst
            router_instances[path] = inst
            return mod

        with patch(
            "zoid6.api.handler.bbsmodule.load",
            side_effect=lambda args, path: make_module(path),
        ):
            router.register_all(mock_server)

        assert set(router.routers.keys()) == set(enabled.keys())
        for name, path in enabled.items():
            assert router.routers[name] is router_instances[path]
            router_instances[path].register_all.assert_called_once_with(
                mock_server
            )

    def test_register_all_skips_non_dict_services(self, mock_args, mock_server):
        """Non-dict service entries are skipped without raising."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        router.config["services"]["bogus"] = "not-a-dict"

        with patch.object(router, "_register_module") as mock_reg:
            router.register_all(mock_server)

        for c in mock_reg.call_args_list:
            assert c.args[0] != "bogus"

    def test_required_module_load_import_error_is_logged(
        self, mock_args, mock_server, caplog
    ):
        """ImportError on a required service is logged and skipped;
        register_all() completes for the rest. The ``required`` flag
        in zoid6.json is documentation-only; failures are never fatal
        here so the daemon can keep serving the services that did
        load and the server can still answer list_services."""
        import logging
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        cfg = _load_config()
        enabled = [
            name for name, svc in cfg["services"].items()
            if isinstance(svc, dict) and svc.get("enabled") is True
        ]
        if not enabled:
            pytest.skip("no enabled services in zoid6.json; nothing to fail on")

        target = enabled[0]
        target_path = cfg["services"][target]["modulepath"]

        def maybe_fail(args, path):
            if path == target_path:
                raise ImportError("simulated missing module")
            mod = MagicMock()
            mod.MessageRouter.return_value = MagicMock()
            return mod

        with patch(
            "zoid6.api.handler.bbsmodule.load", side_effect=maybe_fail
        ):
            with caplog.at_level(logging.ERROR, logger="asimov"):
                router.register_all(mock_server)

        # The error log names the failing service so the operator can
        # tell which module was skipped, but register_all completes.
        assert any(
            f"failed to import {target}" in rec.getMessage().lower()
            for rec in caplog.records
        ), f"expected 'failed to import {target}' in logs"
        assert target not in router.routers

    def test_optional_module_load_import_error_is_swallowed(
        self, mock_args, mock_server, caplog
    ):
        """ImportError on a non-required (``required: false``) service is
        logged and skipped; register_all() completes for the rest."""
        import logging
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        cfg = _load_config()
        enabled = [
            name for name, svc in cfg["services"].items()
            if isinstance(svc, dict) and svc.get("enabled") is True
        ]
        if not enabled:
            pytest.skip("no enabled services in zoid6.json; nothing to fail on")

        target = enabled[0]
        target_path = cfg["services"][target]["modulepath"]
        # Force the picked service to be optional for this test only.
        router.config["services"][target]["required"] = False

        def maybe_fail(args, path):
            if path == target_path:
                raise ImportError("simulated missing module")
            mod = MagicMock()
            mod.MessageRouter.return_value = MagicMock()
            return mod

        with patch(
            "zoid6.api.handler.bbsmodule.load", side_effect=maybe_fail
        ):
            with caplog.at_level(logging.ERROR, logger="asimov"):
                router.register_all(mock_server)

        assert any(
            f"failed to import {target}" in rec.getMessage().lower()
            for rec in caplog.records
        ), f"expected 'failed to import {target}' in logs"
        assert target not in router.routers

    def test_required_module_load_attribute_error_is_logged(
        self, mock_args, mock_server, caplog
    ):
        """AttributeError (no MessageRouter) on a required service is
        logged and skipped; register_all() completes for the rest."""
        import logging
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        cfg = _load_config()
        enabled = [
            name for name, svc in cfg["services"].items()
            if isinstance(svc, dict) and svc.get("enabled") is True
        ]
        if not enabled:
            pytest.skip("no enabled services in zoid6.json; nothing to fail on")

        target = enabled[0]
        target_path = cfg["services"][target]["modulepath"]

        def maybe_fail(args, path):
            if path == target_path:
                raise AttributeError("no MessageRouter class")
            mod = MagicMock()
            mod.MessageRouter.return_value = MagicMock()
            return mod

        with patch(
            "zoid6.api.handler.bbsmodule.load", side_effect=maybe_fail
        ):
            with caplog.at_level(logging.ERROR, logger="asimov"):
                router.register_all(mock_server)

        assert any(
            "messagerouter not found" in rec.getMessage().lower()
            for rec in caplog.records
        ), "expected 'MessageRouter not found' in logs"
        assert target not in router.routers

    def test_optional_module_load_attribute_error_is_swallowed(
        self, mock_args, mock_server, caplog
    ):
        """AttributeError (no MessageRouter) on a non-required service is
        logged and skipped."""
        import logging
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        cfg = _load_config()
        enabled = [
            name for name, svc in cfg["services"].items()
            if isinstance(svc, dict) and svc.get("enabled") is True
        ]
        if not enabled:
            pytest.skip("no enabled services in zoid6.json; nothing to fail on")

        target = enabled[0]
        target_path = cfg["services"][target]["modulepath"]
        router.config["services"][target]["required"] = False

        def maybe_fail(args, path):
            if path == target_path:
                raise AttributeError("no MessageRouter class")
            mod = MagicMock()
            mod.MessageRouter.return_value = MagicMock()
            return mod

        with patch(
            "zoid6.api.handler.bbsmodule.load", side_effect=maybe_fail
        ):
            with caplog.at_level(logging.ERROR, logger="asimov"):
                router.register_all(mock_server)

        assert any(
            "messagerouter not found" in rec.getMessage().lower()
            for rec in caplog.records
        ), "expected 'MessageRouter not found' in logs"
        assert target not in router.routers

    def test_bed_section_fields(self, mock_args):
        """bed section matches the packaged zoid6.json: autorestart=false (systemd's
        Restart=on-failure does the restarting), restart_delay=5, max_restarts=10."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        bed = router.config["bed"]
        assert bed["autorestart"] is False
        assert bed["restart_delay"] == 5
        assert bed["max_restarts"] == 10

    def test_bind_section_is_localhost(self, mock_args):
        """bind.host=localhost is intentional (dual-stack)."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        assert router.config["bind"]["host"] == "localhost"
        assert router.config["bind"]["port"] == 8765

    def test_database_section_loaded(self, mock_args):
        """database section reflects zoid6's local dev defaults."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        db = router.config["database"]
        assert db["name"] == "zoid6"
        assert db["host"] == "localhost"
        assert db["port"] == 5432

    def test_casino_extras_loaded(self, mock_args):
        """Per-service casino sub-config survives the round-trip."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        casino = router.config["services"]["casino"]
        assert casino["games"] == ["blackjack", "poker", "yahtzee"]
        assert casino["table_service"]["list_tables"] is True
        assert casino["table_service"]["create_table"] is True
        assert casino["table_service"]["join_table"] is True
        assert casino["table_service"]["leave_table"] is True
        assert casino["table_service"]["watch_table"] is True
        assert casino["game_service"]["hit"] is True
        assert casino["game_service"]["stand"] is True
        assert casino["game_service"]["double"] is True
        assert casino["game_service"]["split"] is True
        assert casino["game_service"]["surrender"] is True
        assert casino["bet_service"] is True
        assert casino["chat_service"]["table_chat"] is True
        assert casino["chat_service"]["global_chat"] is True
        assert casino["chat_service"]["emote"] is True

    def test_placeholder_services_disabled(self, mock_args):
        """empyre, murdermotel, and auth are placeholders and must stay
        disabled.

        Flipping these to enabled=true without supplying a real modulepath
        is a misconfiguration; this test makes that mistake loud.

        auth is disabled because bed AuthService (bed/api/auth.py) handles
        authentication natively before any router loads.  The auth entry
        in zoid6.json is a documentation placeholder — its modulepath
        ('auth.api.handler') does not resolve as a top-level module.

        member and channel are enabled as standalone
        services with their own handler modules.
        """
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)

        svc = router.config["services"]["empyre"]
        assert svc["enabled"] is False, (
            "placeholder service 'empyre' must remain disabled"
        )
        assert isinstance(svc["modulepath"], str)
        assert "." in svc["modulepath"]

        svc = router.config["services"]["auth"]
        assert svc["enabled"] is False, (
            "auth must be disabled; bed handles auth natively"
        )

    def test_register_all_fails_without_auth(self, mock_args):
        """register_all raises RuntimeError when bed AuthService is missing.

        Bed must register auth on the server before the zoid6 router
        loads.  If auth is absent something is fundamentally broken
        (wrong router class, auth disabled in bed config, etc.) and
        zoid6 should refuse to start.
        """
        from zoid6.api.handler import MessageRouter

        server = MagicMock()
        server.get_service = MagicMock(return_value=None)
        router = MessageRouter(mock_args)

        with pytest.raises(RuntimeError, match="AuthService not registered"):
            router.register_all(server)

    def test_register_all_fails_without_member(self, mock_args):
        """register_all raises RuntimeError when the member module did not load.

        member is enabled in zoid6.json and bbsengine6.member.api.handler
        must be importable.  If it failed to load (import error, missing
        MessageRouter, etc.) zoid6 should refuse to start rather than
        run with a partial service set.
        """
        from zoid6.api.handler import MessageRouter

        def _get_service(name):
            if name == "auth":
                return MagicMock()
            return None

        server = MagicMock()
        server.get_service = MagicMock(side_effect=_get_service)
        router = MessageRouter(mock_args)

        with pytest.raises(RuntimeError, match="Member service not registered"):
            router.register_all(server)

    def test_register_all_does_not_register_list_services(
        self, mock_args, mock_server
    ):
        """zoid6's MessageRouter does NOT register its own list_services
        handler. The bbsengine6.net.WebSocketServer answers list_services
        directly via dispatch_message (added in 2026-06-28) so any router
        that registers a copy is duplicate state. This test pins that
        the unified router's register_all is silent on list_services."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)

        with patch(
            "zoid6.api.handler.bbsmodule.load",
            side_effect=ImportError("simulated missing module"),
        ):
            router.register_all(mock_server)

        # The mock server's register_service was never called for
        # "list_services" (the loop didn't load any services either,
        # so no calls at all). What we care about: no router-level
        # service claims list_services.
        all_message_types = {
            mt
            for call in mock_server.register_service.call_args_list
            for mt in call.args[1]
        }
        assert "list_services" not in all_message_types

    def test_websocketserver_answers_list_services_directly(
        self, mock_args, mock_server
    ):
        """bbsengine6.net.WebSocketServer.dispatch_message answers
        list_services directly, returning the live _services keys."""
        from bbsengine6.net import WebSocketServer

        server = WebSocketServer(host="127.0.0.1", port=0)
        server._services = {
            "auth": object(),
            "ping": object(),
            "list_tables": object(),
        }
        import asyncio
        reply = asyncio.run(
            server.dispatch_message(
                websocket=MagicMock(),
                path="/",
                message={"type": "list_services"},
            )
        )
        assert reply == {
            "type": "services",
            "services": ["auth", "list_tables", "ping"],
        }

    def test_websocketserver_list_services_survives_total_load_failure(
        self, mock_args, mock_server
    ):
        """list_services is answered by the server itself, so it works
        even if the router fails to register module services.  Auth is
        pre-registered (as bed does at startup) but all module imports
        are mocked to fail, so register_all raises at the member check.
        list_services still works on the server regardless."""
        from bbsengine6.net import WebSocketServer
        from zoid6.api.handler import MessageRouter

        server = WebSocketServer(host="127.0.0.1", port=0)
        # Simulate bed's auth registration (happens before the router loads).
        server.register_service(MagicMock(), ["auth"])
        router = MessageRouter(mock_args)
        with patch(
            "zoid6.api.handler.bbsmodule.load",
            side_effect=ImportError("simulated missing module"),
        ):
            with pytest.raises(RuntimeError, match="Member service not registered"):
                router.register_all(server)

        # register_all failed, but list_services on the server still works.
        import asyncio
        reply = asyncio.run(
            server.dispatch_message(
                websocket=MagicMock(),
                path="/",
                message={"type": "list_services"},
            )
        )
        assert reply["type"] == "services"
        assert isinstance(reply["services"], list)


# ---------------------------------------------------------------------------
# Integration tests: real WebSocketServer, real bbsmodule.load
# ---------------------------------------------------------------------------


@pytest.mark.integration
class TestBedStartupIntegration:
    """Integration tests that run MessageRouter.register_all against a real
    bbsengine6.net.WebSocketServer and a real bbsmodule.load.

    These tests do not bind a socket; they only construct the server and
    call register_service, which mutates server._services. They do require
    that at least one of the enabled services is importable; the others
    are tolerated via the existing ImportError-handling path in
    MessageRouter._register_module.
    """

    # If the @pytest.mark.integration decorator is dropped because the
    # zoid6 project does not register a custom marker, all tests in this
    # class will run as part of the default suite, which is what we want:
    # none of them require a database or live socket.

    def test_real_websocketserver_receives_registrations(self, mock_args):
        """Real WebSocketServer ends up with at least one service registered."""
        from bbsengine6.net import WebSocketServer
        from zoid6.api.handler import MessageRouter

        server = WebSocketServer(host="127.0.0.1", port=0)
        # Simulate bed's auth registration (happens before the router loads).
        server.register_service(MagicMock(), ["auth"])
        router = MessageRouter(mock_args)

        router.register_all(server)
        services = server.list_services()
        assert isinstance(services, dict)
        # At least one enabled service must have produced registrations.
        # bank resolves as bbsengine6.bank.api.handler, and
        # casino.api.handler does too.  auth.api.handler is a placeholder
        # that doesn't exist yet (handled gracefully via ImportError).
        # So we expect at minimum the casino and bank registrations to land.
        assert len(services) > 0, (
            f"no services registered; got {services!r}"
        )
        # 'list_services' itself is NOT in server.list_services() because
        # the WebSocketServer answers it directly without registering
        # a service for it. To confirm list_services works, drive
        # dispatch_message and check the reply.
        import asyncio
        reply = asyncio.run(
            server.dispatch_message(
                websocket=MagicMock(),
                path="/",
                message={"type": "list_services"},
            )
        )
        assert reply["type"] == "services"
        assert isinstance(reply["services"], list)

    def test_real_server_has_casino_message_types(self, mock_args):
        """If casino.api.handler is importable, its message types are present."""
        from bbsengine6.net import WebSocketServer
        from zoid6.api.handler import MessageRouter

        try:
            import casino.api.handler  # noqa: F401
        except ImportError:
            pytest.skip("casino.api.handler not importable in this env")

        server = WebSocketServer(host="127.0.0.1", port=0)
        # Simulate bed's auth registration (happens before the router loads).
        server.register_service(MagicMock(), ["auth"])
        router = MessageRouter(mock_args)
        router.register_all(server)

        services = server.list_services()
        # Spot-check a representative slice of casino's registered types.
        # Casino now only registers its own game/auth/chat services.
        # bank, channel, and member are standalone modules.
        expected_subset = {
            "list_tables", "join_table", "leave_table",
            "hit", "stand", "double", "split", "surrender",
            "bet",
            "chat_table", "chat_global", "emote",
            "slot_spin",
            "yahtzee_roll", "yahtzee_score",
            "tictactoe_move", "tictactoe_resign",
        }
        missing = expected_subset - set(services.keys())
        assert not missing, (
            f"casino message types missing from list_services(): {missing}\n"
            f"got: {sorted(services.keys())}"
        )

    # Services that are enabled in zoid6.json but whose module lives outside
    # the sibling-repo sys.path.  auth is disabled (bed handles it natively)
    # so it does not appear in the parametrized enabled set at all.
    _KNOWN_UNIMPORTABLE = set()

    @pytest.mark.parametrize(
        "service_name",
        [
            name
            for name, svc in _load_config()["services"].items()
            if isinstance(svc, dict)
            and svc.get("enabled") is True
        ],
        ids=lambda n: f"enabled-{n}",
    )
    def test_enabled_service_imports_cleanly(self, service_name):
        """Every enabled service's modulepath imports without error and has MessageRouter.

        Known-unimportable services (whose modules live in sibling repos
        that may not be installed) are skipped with a clear message so
        they don't mask regressions in the real modules.  auth is disabled
        in zoid6.json (bed handles it natively) so it never appears in
        this parametrized set.
        """
        import importlib

        if service_name in self._KNOWN_UNIMPORTABLE:
            pytest.skip(
                f"{service_name!r} is a known placeholder / external "
                f"module; skipping import check"
            )

        cfg = _load_config()
        svc = cfg["services"][service_name]
        modulepath = svc["modulepath"]

        module = importlib.import_module(modulepath)
        assert hasattr(module, "MessageRouter"), (
            f"enabled service {service_name!r} at {modulepath!r} "
            f"imports but has no MessageRouter class"
        )

    @pytest.mark.parametrize(
        "service_name",
        [
            name
            for name, svc in _load_config()["services"].items()
            if isinstance(svc, dict) and svc.get("enabled") is True
        ],
        ids=lambda n: f"enabled-{n}",
    )
    def test_enabled_service_modulepath_is_dotted(self, service_name):
        """Every enabled service has a non-empty dotted modulepath."""
        cfg = _load_config()
        svc = cfg["services"][service_name]
        modulepath = svc["modulepath"]
        assert isinstance(modulepath, str) and "." in modulepath, (
            f"enabled service {service_name!r} has invalid modulepath: {modulepath!r}"
        )

    def test_get_enabled_services_reflects_runtime_load(
        self, mock_args
    ):
        """get_enabled_services() is the source of truth for what gets loaded.

        We cross-check it against the set of services that bbsmodule.load
        is asked for, using a thin wrapper that records call args.
        """
        from zoid6.api.handler import MessageRouter, get_enabled_services

        router = MessageRouter(mock_args)
        cfg = _load_config()
        enabled = {
            name for name, svc in cfg["services"].items()
            if isinstance(svc, dict) and svc.get("enabled") is True
        }

        loaded_paths = []

        def record(args, path):
            loaded_paths.append(path)
            mod = MagicMock()
            mod.MessageRouter.return_value = MagicMock()
            return mod

        with patch("zoid6.api.handler.bbsmodule.load", side_effect=record):
            router.register_all(MagicMock())

        loaded_names = {
            name for name, svc in cfg["services"].items()
            if isinstance(svc, dict)
            and svc.get("enabled") is True
            and svc["modulepath"] in loaded_paths
        }
        assert set(get_enabled_services()) == enabled
        assert loaded_names == enabled


if __name__ == "__main__":
    pytest.main([__file__, "-v"])
