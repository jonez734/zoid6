"""Tests for MessageRouter config validation.

Covers ``MessageRouter._validate_config`` which fails loudly on
malformed configs at startup rather than silently skipping modules.
"""
import sys
from unittest.mock import patch

import pytest

sys.path.insert(0, "/home/opencode/data/work/zoid6/src")


class TestMessageRouterValidateConfig:
    """MessageRouter._validate_config rejects malformed configs."""

    def test_validate_dict_passes(self):
        from zoid6.api.handler import MessageRouter

        MessageRouter._validate_config(
            {"services": {"foo": {"enabled": True, "modulepath": "x"}}}
        )

    def test_validate_non_dict_raises(self):
        from zoid6.api.handler import MessageRouter

        with pytest.raises(RuntimeError, match="must be a JSON object"):
            MessageRouter._validate_config([])  # type: ignore[arg-type]

    def test_validate_services_non_dict_raises(self):
        from zoid6.api.handler import MessageRouter

        with pytest.raises(RuntimeError, match="'services' must be an object"):
            MessageRouter._validate_config({"services": "not-a-dict"})

    def test_validate_module_non_dict_raises(self):
        from zoid6.api.handler import MessageRouter

        with pytest.raises(
            RuntimeError, match="services\\.foo must be an object"
        ):
            MessageRouter._validate_config({"services": {"foo": "not-a-dict"}})

    def test_validate_services_missing_ok(self):
        """A config without ``services`` is allowed (no modules to load)."""
        from zoid6.api.handler import MessageRouter

        MessageRouter._validate_config({"bed": {"autorestart": True}})

    def test_load_config_validates_after_loading(self, mock_args, tmp_path):
        """_load_config runs _validate_config before returning.

        A malformed config raises at construction time rather than
        silently being passed to register_all().
        """
        from zoid6.api.handler import MessageRouter

        cfg_path = tmp_path / "zoid6.json"
        cfg_path.write_text('{"services": "not-a-dict"}')

        mock_args.config_file = str(cfg_path)

        with patch("bed.config.load_config", return_value={"services": "not-a-dict"}):
            with pytest.raises(RuntimeError, match="'services' must be an object"):
                MessageRouter(mock_args)
