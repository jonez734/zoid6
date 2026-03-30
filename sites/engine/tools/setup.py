#!/usr/bin/env python

from distutils.core import setup

import time

v = time.strftime("%Y%m%d%H%M")
projectname = "zoidweb2"

setup(
    name=projectname,
    version=v,
    url="http://projects.zoidtechnologies.com/%s/" % (projectname),
    author="Zoid Technologies",
    author_email="%s@projects.zoidtechnologies.com" % (projectname),
    py_modules=["sendnotify"],
    requires=["bbsengine", "ttyio"],
    scripts=["sendnotify"],
)
