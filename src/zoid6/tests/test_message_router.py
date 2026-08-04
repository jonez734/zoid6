#!/usr/bin/env python3
"""Tests for the zoid6 MessageRouter.

These tests use mocks to avoid touching real databases, modules, or sockets.
The router's job is configuration-driven orchestration: load enabled services
from zoid6.json and call each module's MessageRouter.register_all(server).
"""
import sys
from unittest.mock import MagicMock, patch

import pytest

sys.path.insert(0, "/home/opencode/data/work/zoid6/src")


class TestMessageRouterInit:
    """Test MessageRouter initialization."""

    def test_init_with_args(self, mock_args):
        """MessageRouter stores args and loads config."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        assert router.args is mock_args
        assert isinstance(router.routers, dict)

    def test_init_loads_config(self, mock_args):
        """MessageRouter loads zoid6.json on init."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        assert "services" in router.config
        assert "casino" in router.config["services"]

    def test_init_routers_dict_empty(self, mock_args):
        """routers dict is empty until register_all is called."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        assert router.routers == {}


class TestMessageRouterConfigLoad:
    """Test config loading behavior."""

    def test_load_config_returns_dict(self, mock_args):
        """_load_config returns a dict (possibly empty if file missing)."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        cfg = router._load_config()
        assert isinstance(cfg, dict)

    def test_load_config_missing_file_raises(self, mock_args, tmp_path):
        """If zoid6.json missing, _load_config raises FileNotFoundError."""
        from zoid6.api.handler import MessageRouter

        mock_args.config_file = str(tmp_path / "nonexistent.json")
        with pytest.raises(FileNotFoundError):
            MessageRouter(mock_args)


class TestMessageRouterRegisterAll:
    """Test register_all method."""

    def test_register_all_skips_disabled_services(self, mock_args, mock_server):
        """Disabled services in config are not registered."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        with patch.object(router, "_register_module") as mock_reg:
            router.register_all(mock_server)
            registered_names = [c.args[0] for c in mock_reg.call_args_list]
            assert "casino" in registered_names
            assert "member" in registered_names
            assert "channel" in registered_names
            assert "postoffice" in registered_names
            assert "empyre" not in registered_names
            assert "murdermotel" not in registered_names
            assert "mistermcfeely" not in registered_names

    def test_register_all_skips_non_dict_entries(self, mock_args, mock_server):
        """Non-dict service entries are skipped gracefully."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        router.config["services"]["bogus"] = "not-a-dict"
        with patch.object(router, "_register_module") as mock_reg:
            router.register_all(mock_server)
            for c in mock_reg.call_args_list:
                assert c.args[0] != "bogus"

    def test_register_all_uses_bbsmodule_load(self, mock_args, mock_server):
        """register_all uses bbsengine6.module.load for module loading."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        with patch("zoid6.api.handler.bbsmodule.load") as mock_load:
            mock_module = MagicMock()
            mock_router_instance = MagicMock()
            mock_module.MessageRouter.return_value = mock_router_instance
            mock_load.return_value = mock_module

            router.register_all(mock_server)
            mock_load.assert_called()
            mock_router_instance.register_all.assert_called_with(mock_server)
            assert "casino" in router.routers

    def test_register_all_handles_import_error(self, mock_args, mock_server, caplog):
        """ImportError for a module is caught and logged, doesn't crash."""
        import logging
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        with patch(
            "zoid6.api.handler.bbsmodule.load",
            side_effect=ImportError("missing"),
        ):
            with caplog.at_level(logging.ERROR, logger="asimov"):
                router.register_all(mock_server)
        assert "casino" not in router.routers
        assert any(
            "failed to import casino" in rec.getMessage().lower()
            for rec in caplog.records
        ), f"expected 'failed to import casino' in logs, got {[r.getMessage() for r in caplog.records]}"

    def test_register_all_handles_missing_message_router_class(
        self, mock_args, mock_server, caplog
    ):
        """AttributeError (no MessageRouter) is caught and logged."""
        import logging
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        with patch(
            "zoid6.api.handler.bbsmodule.load",
            side_effect=AttributeError("no MessageRouter"),
        ):
            with caplog.at_level(logging.ERROR, logger="asimov"):
                router.register_all(mock_server)
        assert "casino" not in router.routers
        assert any(
            "messagerouter not found" in rec.getMessage().lower()
            for rec in caplog.records
        ), f"expected 'messagerouter not found' in logs, got {[r.getMessage() for r in caplog.records]}"

    def test_register_all_handles_generic_exception(
        self, mock_args, mock_server, capsys
    ):
        """Generic exceptions are caught and logged, don't crash other modules."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        with patch(
            "zoid6.api.handler.bbsmodule.load",
            side_effect=RuntimeError("boom"),
        ):
            router.register_all(mock_server)
        assert "casino" not in router.routers


class TestMessageRouterRegisterModule:
    """Test the per-module _register_module method."""

    def test_register_module_missing_modulepath(self, mock_args, mock_server, caplog):
        """If modulepath missing, skip with a message and don't raise."""
        import logging
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        with caplog.at_level(logging.WARNING, logger="asimov"):
            router._register_module("foo", {}, mock_server)
        assert "foo" not in router.routers
        assert any(
            "no modulepath" in rec.getMessage().lower()
            for rec in caplog.records
        ), f"expected 'no modulepath' in logs, got {[r.getMessage() for r in caplog.records]}"

    def test_register_module_success(self, mock_args, mock_server):
        """Successful module registration stores router in self.routers."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        with patch("zoid6.api.handler.bbsmodule.load") as mock_load:
            mock_module = MagicMock()
            mock_router_instance = MagicMock()
            mock_module.MessageRouter.return_value = mock_router_instance
            mock_load.return_value = mock_module

            router._register_module(
                "casino",
                {"modulepath": "casino.api.handler", "enabled": True},
                mock_server,
            )

        assert router.routers["casino"] is mock_router_instance
        mock_router_instance.register_all.assert_called_once_with(mock_server)


class TestGetConfigModuleFunctions:
    """Test module-level helper functions."""

    def test_get_config_returns_dict(self):
        """get_config returns a dict from zoid6.json."""
        from zoid6.api.handler import get_config

        cfg = get_config()
        assert isinstance(cfg, dict)

    def test_get_config_includes_services(self):
        """get_config result includes the services section."""
        from zoid6.api.handler import get_config

        cfg = get_config()
        assert "services" in cfg

    def test_get_enabled_services_returns_list(self):
        """get_enabled_services returns a list of strings."""
        from zoid6.api.handler import get_enabled_services

        result = get_enabled_services()
        assert isinstance(result, list)
        for name in result:
            assert isinstance(name, str)

    def test_get_enabled_services_default_casino_enabled(self):
        """Default config has casino enabled."""
        from zoid6.api.handler import get_enabled_services

        result = get_enabled_services()
        assert "casino" in result

    def test_get_enabled_services_excludes_disabled(self):
        """Disabled services are excluded from result."""
        from zoid6.api.handler import get_enabled_services

        result = get_enabled_services()
        assert "empyre" not in result
        assert "murdermotel" not in result


class TestModuleLoadUsesBbsengine6:
    """Verify the router uses bbsengine6.module per the TODO spec."""

    def test_handler_imports_bbsengine6_module(self):
        """zoid6.api.handler imports bbsengine6.module as bbsmodule."""
        import zoid6.api.handler as handler

        assert hasattr(handler, "bbsmodule")
        assert hasattr(handler.bbsmodule, "load")
        assert hasattr(handler.bbsmodule, "get")

    def test_register_module_calls_bbsmodule_load(self, mock_args, mock_server):
        """_register_module calls bbsmodule.load with the configured modulepath."""
        from zoid6.api.handler import MessageRouter

        router = MessageRouter(mock_args)
        with patch("zoid6.api.handler.bbsmodule.load") as mock_load:
            mock_module = MagicMock()
            mock_module.MessageRouter.return_value = MagicMock()
            mock_load.return_value = mock_module

            router._register_module(
                "casino",
                {"modulepath": "casino.api.handler"},
                mock_server,
            )

            mock_load.assert_called_once()
            args, kwargs = mock_load.call_args
            assert args[0] is mock_args
            assert args[1] == "casino.api.handler"


class TestPackageMetadata:
    """Test package-level metadata."""

    def test_version_is_defined(self):
        """zoid6.__version__ is defined."""
        import zoid6

        assert hasattr(zoid6, "__version__")
        assert isinstance(zoid6.__version__, str)

    def test_api_subpackage_importable(self):
        """zoid6.api is importable."""
        import zoid6.api

        assert zoid6.api is not None

    def test_message_router_importable(self):
        """MessageRouter can be imported from zoid6.api.handler."""
        from zoid6.api.handler import MessageRouter

        assert MessageRouter is not None

    def test_bed_json_shipped(self):
        """data/zoid6.json is shipped with the package."""
        from pathlib import Path

        from zoid6.api.handler import get_bundled_config_path

        bed_json = Path(get_bundled_config_path())
        assert bed_json.exists()
        assert bed_json.is_file()
