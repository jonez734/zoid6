import os
import sys

from zoid6.api.handler import get_bundled_config_path


_CONFIG_FILE_ENV = "ZOID6_CONFIG"
_DEFAULT_ROUTER = "zoid6.api.handler.MessageRouter"


def _resolve_config_path() -> str:
    override = os.environ.get(_CONFIG_FILE_ENV)
    if override:
        return override
    etc_path = "/etc/zoid6/zoid6.json"
    if os.path.isfile(etc_path):
        return etc_path
    return get_bundled_config_path()


def main() -> None:
    # Answer --version here, before bed's parser sees argv, so the output is
    # zoid6's version rather than bed's.
    if "--version" in sys.argv[1:]:
        from zoid6._version import __version__ as zoid6_version

        print(f"zoid6 {zoid6_version}")
        return

    config_path = _resolve_config_path()
    sys.argv = [
        sys.argv[0],
        "--config",
        config_path,
        "--router",
        _DEFAULT_ROUTER,
        *sys.argv[1:],
    ]
    from bed.main import main as _bed_main

    _bed_main()
