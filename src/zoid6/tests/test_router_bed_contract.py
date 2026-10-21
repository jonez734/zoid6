"""zoid6's MessageRouter accepts every keyword bed passes it.

bed builds the router with channel_state (and token kwargs when auth is on).
A constructor that rejects one of them stops bed from starting, so this pins
the accepted keywords to what bed sends.
"""

import inspect
import types

import pytest

from zoid6.api.handler import MessageRouter

BED_KWARGS = ("channel_state", "session_registry", "secret", "token_store", "instance_id")


def test_router_accepts_all_bed_router_kwargs():
    params = inspect.signature(MessageRouter.__init__).parameters
    missing = [k for k in BED_KWARGS if k not in params]
    assert not missing, f"MessageRouter does not accept bed kwargs: {missing}"


def test_channel_state_is_stored_on_the_router(monkeypatch):
    # Config loading needs a real --config, so stub it; this test is about the
    # constructor keeping the shared channel state.
    monkeypatch.setattr(MessageRouter, "_load_config", lambda self: {"services": {}})
    sentinel = object()
    router = MessageRouter(types.SimpleNamespace(config_file=None), channel_state=sentinel)
    assert router.channel_state is sentinel
