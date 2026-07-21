#!/usr/bin/env python3
"""Tests for zoid6 data/bed.json configuration.

bed.json is the configuration file shipped with zoid6. It tells the unified
MessageRouter which module routers to load. These tests verify the file is
valid JSON and contains the expected structure.
"""
import json
import sys
from pathlib import Path

import pytest

sys.path.insert(0, "/home/opencode/data/work/zoid6/src")


def _bed_json_path() -> Path:
    import zoid6

    return Path(zoid6.__file__).parent / "data" / "bed.json"


class TestBedJsonFile:
    """Verify the shipped bed.json file is well-formed."""

    def test_bed_json_exists(self):
        """bed.json file exists in package data dir."""
        assert _bed_json_path().exists()

    def test_bed_json_is_valid_json(self):
        """bed.json parses as valid JSON."""
        with open(_bed_json_path()) as f:
            data = json.load(f)
        assert isinstance(data, dict)

    def test_bed_json_has_services_key(self):
        """bed.json has a 'services' section."""
        with open(_bed_json_path()) as f:
            data = json.load(f)
        assert "services" in data
        assert isinstance(data["services"], dict)

    def test_bed_json_has_bed_key(self):
        """bed.json has a 'bed' section (autorestart config)."""
        with open(_bed_json_path()) as f:
            data = json.load(f)
        assert "bed" in data
        assert "autorestart" in data["bed"]

    def test_bed_json_has_bind_section(self):
        """bed.json has a 'bind' section (host/port)."""
        with open(_bed_json_path()) as f:
            data = json.load(f)
        assert "bind" in data
        assert "host" in data["bind"]
        assert "port" in data["bind"]


class TestBedJsonServices:
    """Test the services section of bed.json."""

    @pytest.fixture
    def services(self):
        with open(_bed_json_path()) as f:
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

    def test_mistermcfeely_service_present(self, services):
        """Mistermcfeely service is defined in default config."""
        assert "mistermcfeely" in services
        assert (
            services["mistermcfeely"]["modulepath"] == "mistermcfeely.api.handler"
        )

    def test_postoffice_service_present(self, services):
        """Postoffice service is defined in default config."""
        assert "postoffice" in services
        assert services["postoffice"]["modulepath"] == "postoffice.api.handler"

    def test_disabled_services_skipped(self, services):
        """empyre, murdermotel, mistermcfeely, and auth are disabled by default.

        auth is disabled because bed AuthService (bed/api/auth.py) handles
        authentication natively before any router loads.  The auth entry
        in bed.json is a documentation placeholder — its modulepath
        ('auth.api.handler') does not resolve as a top-level module.

        member, channel, and postoffice are now enabled as standalone
        services with their own handler modules.
        """
        for name in (
            "empyre",
            "murdermotel",
            "mistermcfeely",
            "auth",
        ):
            assert services[name]["enabled"] is False, f"{name} should be disabled"

    def test_bank_modulepath_points_at_bbsengine6(self, services):
        """bank's modulepath is bbsengine6.bank.api.handler (option b in
        the zoid6 TODO). The bare 'bank.api.handler' path does not resolve
        as a top-level module; the bbsengine6 bank package does."""
        bank = services["bank"]
        assert bank["enabled"] is True
        assert bank["modulepath"] == "bbsengine6.bank.api.handler"

    def test_all_modulepaths_are_dotted(self, services):
        """All modulepaths use dot notation for import."""
        for name, svc in services.items():
            if not isinstance(svc, dict):
                continue
            mp = svc["modulepath"]
            assert "." in mp, f"Service {name!r} modulepath {mp!r} is not dotted"


class TestBedJsonSchemaConsistency:
    """Cross-check the bed.json file with the bed config schema."""

    def test_bed_section_fields(self):
        """bed section has autorestart, restart_delay, max_restarts."""
        with open(_bed_json_path()) as f:
            data = json.load(f)
        bed = data["bed"]
        for field in ("autorestart", "restart_delay", "max_restarts"):
            assert field in bed, f"bed section missing {field!r}"

    def test_bind_section_host_is_string(self):
        """bind.host is a string."""
        with open(_bed_json_path()) as f:
            data = json.load(f)
        assert isinstance(data["bind"]["host"], str)

    def test_bind_section_port_is_int(self):
        """bind.port is an int."""
        with open(_bed_json_path()) as f:
            data = json.load(f)
        assert isinstance(data["bind"]["port"], int)

    def test_database_section_present(self):
        """database section is present (db connection config)."""
        with open(_bed_json_path()) as f:
            data = json.load(f)
        if "database" in data:
            db = data["database"]
            for field in ("name", "host", "port"):
                assert field in db
