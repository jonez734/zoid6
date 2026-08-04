import importlib.resources as resources

from ..lib import PACKAGENAME


def get(name):
    return resources.open_text(PACKAGENAME + ".data", name)


def path(name):
    return str(resources.files(PACKAGENAME + ".data") / name)
