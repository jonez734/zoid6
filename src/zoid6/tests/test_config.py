#!/usr/bin/env python3
"""Tests for zoid6 data/zoid6.json configuration.

zoid6.json is the configuration file shipped with zoid6. It tells the unified
MessageRouter which module routers to load. These tests verify the file is
valid JSON and contains the expected structure.
"""
import json
import sys
from pathlib import Path

import pytest

sys.path.insert(0, "/home/opencode/data/work/zoid6/src")


def _config_path() -> Path:
    from zoid6.api.handler import get_bundled_config_path

    return Path(get_bundled_config_path())


class TestConfigFile:
    """Verify the shipped zoid6.json file is well-formed."""

    def test_config_exists(self):
        """zoid6.json file exists in package data dir."""
        assert _config_path().exists()

    def test_config_is_valid_json(self):
        """zoid6.json parses as valid JSON."""
        with open(_config_path()) as f:
            data = json.load(f)
        assert isinstance(data, dict)

    def test_config_has_services_key(self):
        """zoid6.json has a 'services' section."""
        with open(_config_path()) as f:
            data = json.load(f)
        assert "services" in data
        assert isinstance(data["services"], dict)

    def test_config_has_bed_key(self):
        """zoid6.json has a 'bed' section (autorestart config)."""
        with open(_config_path()) as f:
            data = json.load(f)
        assert "bed" in data
        assert "autorestart" in data["bed"]

    def test_config_has_bind_section(self):
        """zoid6.json has a 'bind' section (host/port)."""
        with open(_config_path()) as f:
            data = json.load(f)
        assert "bind" in data
        assert "host" in data["bind"]
        assert "port" in data["bind"]


class TestConfigServices:
    """Test the services section of zoid6.json."""

    @pytest.fixture
    def services(self):
        with open(_config_path()) as f:
            return json.load(f)["services"]

    def test_services_have_required_keys(self, services):
        """Each service entry has at minimum 'enabled' and 'modulepath'."""
        for name, svc in services.items():
            if not isinstance(svc, dict):
                continue
            assert "enabled" in svc, f"Service {name!r} missing 'enabled'"
            assert "modulepath" in svc, f"Service {name!r} missing 'modulepath'"

    def test_casino_service_present(self, services):
        """Casino service is defined in default config."""
        assert "casino" in services
        assert services["casino"]["modulepath"] == "casino.api.handler"

    def test_casino_enabled_by_default(self, services):
        """Casino is enabled in default config."""
        assert services["casino"]["enabled"] is True

    def test_empyre_service_present(self, services):
        """Empyre service is defined in default config."""
        assert "empyre" in services
        assert services["empyre"]["modulepath"] == "empyre.api.handler"

    def test_murdermotel_service_present(self, services):
        """Murdermotel service is defined in default config."""
        assert "murdermotel" in services
        assert services["murdermotel"]["modulepath"] == "murdermotel.api.handler"

    def test_disabled_services_skipped(self, services):
        """empyre, murdermotel, and auth are disabled by default.

        auth is disabled because bed AuthService (bed/api/auth.py) handles
        authentication natively before any router loads.  The auth entry
        in zoid6.json is a documentation placeholder — its modulepath
        ('auth.api.handler') does not resolve as a top-level module.

        member and channel are enabled as standalone services with their
        own handler modules.
        """
        for name in (
            "empyre",
            "murdermotel",
            "auth",
        ):
            assert services[name]["enabled"] is False, f"{name} should be disabled"

    def test_bank_is_disabled_so_bed_owns_bank_messages(self, services):
        """bank_* message types are served only by bed's BankService.

        zoid6's bank entry would register the same message types a second
        time with bbsengine6's direct-table BankServiceHandler, shadowing
        bed's definer-function handler. It must stay disabled.
        """
        bank = services["bank"]
        assert bank["enabled"] is False, "bank must stay disabled; bed owns bank_*"

    def test_all_modulepaths_are_dotted(self, services):
        """All modulepaths use dot notation for import."""
        for name, svc in services.items():
            if not isinstance(svc, dict):
                continue
            mp = svc["modulepath"]
            assert "." in mp, f"Service {name!r} modulepath {mp!r} is not dotted"


class TestConfigSchemaConsistency:
    """Cross-check the zoid6.json file with the bed config schema."""

    def test_bed_section_fields(self):
        """bed section has autorestart, restart_delay, max_restarts."""
        with open(_config_path()) as f:
            data = json.load(f)
        bed = data["bed"]
        for field in ("autorestart", "restart_delay", "max_restarts"):
            assert field in bed, f"bed section missing {field!r}"

    def test_bind_section_host_is_string(self):
        """bind.host is a string."""
        with open(_config_path()) as f:
            data = json.load(f)
        assert isinstance(data["bind"]["host"], str)

    def test_bind_section_port_is_int(self):
        """bind.port is an int."""
        with open(_config_path()) as f:
            data = json.load(f)
        assert isinstance(data["bind"]["port"], int)

    def test_database_section_present(self):
        """database section is present (db connection config)."""
        with open(_config_path()) as f:
            data = json.load(f)
        if "database" in data:
            db = data["database"]
            for field in ("name", "host", "port"):
                assert field in db


class TestConfigFactorySync:
    """Guard against drift between src/zoid6/data/zoid6.json and the
    usr/share/factory/etc/zoid6/zoid6.json FHS factory default. The
    factory default is what `make -C src install-etc` ships to
    /etc/zoid6/zoid6.json on a real install, so a stale copy silently
    ships an out-of-date config to production hosts.
    """

    def test_factory_default_matches_source(self):
        """usr/share/factory/etc/zoid6/zoid6.json must equal src/zoid6/data/zoid6.json."""
        from pathlib import Path

        src = _config_path()
        factory = Path(
            "/home/opencode/data/work/zoid6/usr/share/factory/etc/zoid6/zoid6.json"
        )
        assert src.read_bytes() == factory.read_bytes(), (
            f"{factory} is out of date; run `make -C zoid6/src factory-sync` "
            "to sync from src/zoid6/data/zoid6.json"
        )
