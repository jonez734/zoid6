#!/usr/bin/env python3
"""Pick the newest wheel for each package from a directory, for a deploy.

A wheel directory holds many builds of each package. Pip, given the directory,
installs the highest PEP 440 version, which is not always the build a deploy wants:
letteredolive has bare-timestamp builds (letteredolive-202610232249) that sort above
the real 0.0.2.dev builds. So each package is resolved explicitly:

  * the package's version line (its PREFIX, e.g. 0.4.0.dev), and
  * the newest build within that line, by the 14-digit YYYYMMDDHHMMSS stamp
    that scripts/next_version.sh appends to every build.

Usage:
    resolve_wheels.py WHEELS_DIR [PACKAGE ...]

Prints one absolute wheel path per package, in the order given, on stdout.
Exits 1 with a message on stderr if a package has no wheel on its version line.
"""

import os
import re
import sys

# The version line each package is deployed from. Bump the line here when a package
# starts a new one; nothing else in the deploy script names a wheel.
PREFIXES = {
    "bed": "0.4.0.dev",
    "bbsengine6": "0.4.0.dev",
    "letteredolive": "0.0.2.dev",
}


def resolve(wheels_dir: str, package: str) -> str:
    prefix = PREFIXES.get(package)
    if prefix is None:
        raise KeyError(f"no version line configured for {package!r}")
    pattern = re.compile(
        rf"^{re.escape(package)}-{re.escape(prefix)}(\d{{14}})-py3-none-any\.whl$"
    )
    best = None
    for name in os.listdir(wheels_dir):
        m = pattern.match(name)
        if m and (best is None or m.group(1) > best[0]):
            best = (m.group(1), name)
    if best is None:
        raise FileNotFoundError(f"no {package} wheel for {prefix}* in {wheels_dir}")
    return os.path.join(wheels_dir, best[1])


def main(argv: list[str]) -> int:
    if len(argv) < 2:
        print(__doc__, file=sys.stderr)
        return 2
    wheels_dir, packages = argv[1], argv[2:] or list(PREFIXES)
    if not os.path.isdir(wheels_dir):
        print(f"resolve_wheels: not a directory: {wheels_dir}", file=sys.stderr)
        return 2
    try:
        for package in packages:
            print(resolve(wheels_dir, package))
    except (KeyError, FileNotFoundError) as e:
        print(f"resolve_wheels: {e}", file=sys.stderr)
        return 1
    return 0


if __name__ == "__main__":
    sys.exit(main(sys.argv))
