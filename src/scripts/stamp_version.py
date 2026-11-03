"""Write a package's _version.py into a build copy, in the shape that package reads.

Usage: stamp_version.py COPY_DIR PACKAGE VERSION GITHASH

Runs only on a scratch copy (the wheel build's .src-copy), never on a checkout, so a build leaves no
_version.py behind in a source tree. Each package reads a different name set, so the shape is per package:
bed, bbsengine6, and zoid6 use __version__ / __datestamp__ / __githash__; casino uses version / githash /
datestamp.
"""

import os
import sys
from datetime import datetime, timezone

# package -> (directory inside the copy, shape)
TARGETS = {
    "bed": ("src/bed", "dunder"),
    "bbsengine6": ("py/src/bbsengine6", "dunder"),
    "zoid6": ("src/zoid6", "dunder"),
    "casino": ("src/casino", "plain"),
}


def main(argv):
    if len(argv) != 4:
        print(__doc__, file=sys.stderr)
        return 2
    copy, package, version, githash = argv
    if package not in TARGETS:
        print(f"stamp_version: unknown package {package!r}", file=sys.stderr)
        return 2
    rel, shape = TARGETS[package]
    datestamp = datetime.now(timezone.utc).strftime("%Y%m%d%H%M")
    path = os.path.join(copy, rel, "_version.py")
    if not os.path.isdir(os.path.dirname(path)):
        print(f"stamp_version: {path} has no package directory", file=sys.stderr)
        return 1
    if shape == "dunder":
        text = (
            f'__version__ = "{version}"\n'
            f'__datestamp__ = "{datestamp}"\n'
            f'__githash__ = "{githash}"\n'
        )
    else:
        text = (
            f'version = "{version}"\n'
            f'githash = "{githash}"\n'
            f'datestamp = "{datestamp}"\n'
        )
    with open(path, "w", encoding="utf-8") as fh:
        fh.write(text)
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv[1:]))
