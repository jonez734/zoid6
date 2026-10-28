PROJECT = zoid6
OUTDIR = /srv/repo/$(PROJECT)/
VERSION := $(shell date +%Y%m%d%H%M)

PYTHON ?= python3
PIP ?= $(PYTHON) -m pip
PYTEST ?= $(PYTHON) -m pytest

SRC := .
PACKAGE := zoid6

FACTORY_DIR = $(CURDIR)/../usr/share/factory/etc/$(PROJECT)
ETC_DIR ?= /etc/$(PROJECT)
TMPFILES_SRC = $(PACKAGE)/daemon/$(PROJECT).tmpfiles
TMPFILES_DST = /usr/lib/tmpfiles.d/$(PROJECT).conf
SYSUSERS_SRC = $(PACKAGE)/daemon/$(PROJECT).sysusers
SYSUSERS_DST = /usr/lib/sysusers.d/$(PROJECT).conf
# One source for the zoid6 unit: the bed repo copy, which bed and zoid6 share.
UNIT_SRC = $(BED_DIR)/src/bed/daemon/$(PROJECT).service
UNIT_DST = /usr/lib/systemd/system/$(PROJECT).service

VENV_DIR ?= /home/zoid6/venv
VENV_OWNER ?= zoid6
VENV_GROUP ?= zoid6

BED_DIR ?= $(CURDIR)/../../bed
WHEEL_DIR ?= $(HOME)/wheel-build/zoid6-current
BBSENGINE_DIR ?= $(CURDIR)/../../bbsengine6/py
CASINO_DIR ?= $(CURDIR)/../../casino

# User-writable install prefix (no-sudo flow). Production FHS layout
# uses /etc/zoid6 + /var/lib/zoid6 + /usr/lib/systemd/system instead;
# install-sysusers / install-tmpfiles / install-systemd / install-etc
# are unchanged and still need sudo.
#
# These only override the FHS defaults when one of the user-mode targets
# is the goal; otherwise the FHS targets (install-etc, install-venv,
# install-systemd, install-fhs) keep using /etc/zoid6 + /var/lib/zoid6.
ifneq ($(filter install-user start stop status restart install-systemd-user uninstall-systemd-user uninstall-user,$(MAKECMDGOALS)),)
PREFIX           ?= $(HOME)/.local/zoid6
VENV_DIR         = $(PREFIX)/venv
ETC_DIR          = $(PREFIX)/etc
LOG_DIR          = $(PREFIX)/log
RUN_DIR          = $(PREFIX)/run
DATA_DIR         = $(PREFIX)/data
SYSTEMD_USER_DIR ?= $(HOME)/.config/systemd/user
endif

.PHONY: all help install install-dev test test-cov test-verbose lint clean uninstall egg-info
.PHONY: build version rename-sdist sign release stale-build
.PHONY: install-etc install-tmpfiles install-fhs uninstall-etc uninstall-tmpfiles factory-sync
.PHONY: install-sysusers uninstall-sysusers
.PHONY: install-venv uninstall-venv restorecon
.PHONY: install-systemd uninstall-systemd uninstall-fhs
.PHONY: deploy-tui deploy-prod
.PHONY: reassign-bootstrap-owners install-user start stop status restart
.PHONY: install-systemd-user uninstall-systemd-user uninstall-user

all: help

help:
	@echo "zoid6 - Unified BBS module router"
	@echo ""
	@echo "Targets:"
	@echo "  install       Install zoid6 in editable mode (pip install -e .)"
	@echo "  install-dev   Install zoid6 with test extras"
	@echo "  install-fhs   Install FHS system files (sysusers + tmpfiles + venv + systemd + etc)"
	@echo "  install-etc   Install /etc/zoid6/zoid6.json from factory defaults"
	@echo "  install-tmpfiles Install /usr/lib/tmpfiles.d/zoid6.conf"
	@echo "  install-sysusers Install /usr/lib/sysusers.d/zoid6.conf (creates zoid6 user/group)"
	@echo "  install-venv  Ensure shared venv at $(VENV_DIR) (owner: $(VENV_OWNER):$(VENV_GROUP))"
	@echo "  install-systemd Install /usr/lib/systemd/system/zoid6.service (@VENV_DIR@ templated)"
	@echo "  uninstall-etc Remove /etc/zoid6/zoid6.json"
	@echo "  uninstall-tmpfiles Remove tmpfiles drop-in"
	@echo "  uninstall-sysusers Remove sysusers drop-in"
	@echo "  uninstall-venv Remove $(VENV_DIR)"
	@echo "  uninstall-systemd Stop, disable, and remove the zoid6.service unit"
	@echo "  restorecon    Relabel $(VENV_DIR)/bin for SELinux"
	@echo "  factory-sync  Copy in-tree data/ + daemon/ to usr/share/factory/"
	@echo "  test          Run tests with pytest"
	@echo "  test-verbose  Run tests with verbose output"
	@echo "  test-cov      Run tests with coverage report"
	@echo "  lint          Run pyflakes on package"
	@echo "  egg-info      Regenerate egg-info metadata"
	@echo "  uninstall     Uninstall zoid6"
	@echo "  clean         Remove build artifacts, caches, and egg-info"
	@echo "  version       Stamp zoid6/_version.py with date + git hash"
	@echo "  build         Build sdist+wheel into $(OUTDIR)"
	@echo "  rename-sdist  Rename built sdist to include -src suffix"
	@echo "  sign          GPG-detach-sign every artifact in $(OUTDIR)"
	@echo "  release       clean + version + build + rename-sdist + sign"
	@echo "  deploy-tui    install from wheel in $(OUTDIR); DEPLOY_EDITABLE=1 makes it editable"
	@echo "  deploy-prod   install-fhs (sudo umbrella: sysusers + tmpfiles + venv + systemd + etc)"

install: version
	$(PIP) install $(PIP_UPGRADE_FLAG) --no-cache-dir -e $(SRC)

install-dev: install
	$(PIP) install $(PIP_UPGRADE_FLAG) pytest pytest-cov

test:
	cd $(SRC) && $(PYTEST) $(PACKAGE)/tests -v

test-verbose:
	cd $(SRC) && $(PYTEST) $(PACKAGE)/tests -vv

test-cov:
	cd $(SRC) && $(PYTEST) $(PACKAGE)/tests --cov=$(PACKAGE) --cov-report=term-missing

lint:
	$(PYTHON) -m pyflakes $(PACKAGE) || true

egg-info:
	$(PIP) install $(PIP_UPGRADE_FLAG) -e $(SRC) --no-deps --force-reinstall --no-build-isolation

uninstall:
	$(PIP) uninstall -y $(PACKAGE)

# Move aside any build/dist/egg-info directory the project holds before a build.
# A tree left by another user cannot be deleted, but renaming it needs only write
# access to this project directory. The stale tree is kept under *.stale.<timestamp>.
stale-build:
	@ts=$$(date +%Y%m%d%H%M%S); \
	for d in build dist *.egg-info; do \
		for x in $$d; do \
			[ -e "$$x" ] || continue; \
			mv "$$x" "$$x.stale.$$ts" && echo "stale-build: moved $$x -> $$x.stale.$$ts"; \
		done; \
	done

clean: stale-build
	-rm -rf build dist
	-rm -rf *.egg-info
	-find . -type d -name __pycache__ -exec rm -rf {} +
	-find . -type d -name .pytest_cache -exec rm -rf {} +
	-find . -type d -name .ruff_cache -exec rm -rf {} +
	-find . -type d -name .mypy_cache -exec rm -rf {} +

version:
	@echo '__version__ = "0.0.3.dev$(VERSION)"' > $(PROJECT)/_version.py
	@echo '__datestamp__ = "$(VERSION)"' >> $(PROJECT)/_version.py
	@echo '__githash__ = "'`git log -1 --format='%H' 2>/dev/null | cut -c 1-16`'"' >> $(PROJECT)/_version.py
	@cat $(PROJECT)/_version.py

# Make sure $(1)/build/ exists with mode 1775 (sticky + rwxrwxr-x) before
# invoking `python -m build`. Mode 1775 is intentional:
#   - sticky (t): only the owner of a file inside may delete/rename it,
#     so concurrent builds under a shared group can't stomp each other.
#   - setgid (s) is intentionally NOT set: setuptools' shutil.copystat
#     mirrors build/'s mode onto the freshly-created dist-info dir, and
#     a setgid'd dist-info EPERMs the subsequent bdist_wheel step in
#     SELinux-enforcing + NoNewPrivs containers (we lack CAP_FSETID).
#   - group write (g+w): any user in the build group can rebuild
#     without needing to chown.
# The chmod is expressed as `chmod g-s,+t` (drop the setgid bit the
# parent dir inherited onto the freshly-mkdir'd build/, then add the
# sticky bit). The numeric form `chmod 1775` is functionally equivalent
# but fails on BTRFS+SELinux setups where the parent directory's
# setgid bit blocks the owner from clearing it via the numeric mode
# (`chmod: Operation not permitted` on a dir the caller owns). The
# symbolic form works because the kernel only restricts numeric-mode
# changes that would remove the inherited setgid bit; `g-s` is
# permitted regardless of where the bit came from.
#
# If $(1)/build/ exists but is owned by a different user (e.g. left over
# from a prior build run as a different uid), rename it out of the way
# first. The parent dir is group-writable in this tree so the rename is
# permitted even when we don't own the build/ contents. Without this,
# the subsequent chmod fails with EPERM and the build aborts.
# Mirrors bed/Makefile:189-194 (see also zoid6/TODO.md "PREPARE_BUILD
# standardization (cross-project)").
#
# Note: zoid6's TODO previously marked src/Makefile exempt because
# `install` is `pip install -e .` (no `python -m build`). The `build`
# target still runs `python -m build`, so the EPERM cascade applies
# to it; adding the macro here is defense-in-depth so a future
# `make build` (or `make deploy-tui` chaining into `build`) can't
# regress on the EPERM.
PREPARE_BUILD = \
	if [ -d $(1)/build ] && [ ! -O $(1)/build ]; then \
		mv $(1)/build $(1)/build.stale.$$ 2>/dev/null || true; \
	fi; \
	mkdir -p $(1)/build && chmod g-s,+t $(1)/build

build: clean version
	$(call PREPARE_BUILD,$(CURDIR))
	$(PYTHON) -m build --outdir $(OUTDIR)

rename-sdist:
	@for f in $(OUTDIR)/*.tar.gz; do \
		if [ -f "$$f" ] && echo "$$f" | grep -vq '\-src\.tar\.gz' ; then \
			mv "$$f" "$${f%.tar.gz}-src.tar.gz"; \
			echo "Renamed $$f -> $${f%.tar.gz}-src.tar.gz"; \
		fi \
	done

sign:
	@for f in $(OUTDIR)/*; do \
		if [ -f "$$f" ] && [ ! -f "$$f.asc" ] && [ "$${f##*.}" != "asc" ]; then \
			gpg --armor --detach-sign "$$f"; \
			echo "Signed $$f"; \
		fi \
	done

release: clean version build rename-sdist sign

# DEPLOY_EDITABLE is set by `deploytool --editable`. When unset,
# deploy-tui installs zoid6 from the freshly-built wheel in
# $(OUTDIR). When set, it installs editable from the source tree
# (matches the previous `deploy-tui: install` shape, which was
# always editable via `pip install -e .`). Picking the newest wheel
# via `ls -t | head -1` so the recipe is robust to multiple wheels
# accumulating in OUTDIR over time (Phase 0 lands wheels there).
DEPLOY_EDITABLE ?=

# DEPLOY_UPGRADE is set by `deploytool --upgrade` (default true) and
# unset by `--no-upgrade`. When "1", every `pip install` in this
# Makefile (except `pip install --upgrade pip`, which always upgrades
# pip itself — stale pip breaks everything downstream) passes
# `--upgrade`. Empty = prior no-op-if-version-matches behavior.
DEPLOY_UPGRADE ?=
PIP_UPGRADE_FLAG := $(if $(filter 1,$(DEPLOY_UPGRADE)),--upgrade,)
# TODO(verify-install): after the wheel `pip install` below, compare the
# wheel's METADATA Version against `pip show zoid6`. See zoidoffice/src/
# Makefile's VERIFY_INSTALL variable for the reference implementation.
# The editable branch installs from source, not a wheel, so no check there.
deploy-tui: build
ifeq ($(DEPLOY_EDITABLE),1)
	$(PIP) install $(PIP_UPGRADE_FLAG) --no-cache-dir -e .
	-rm -rf *.egg-info
else
	@WHEEL=$$(ls -t $(OUTDIR)/$(PROJECT)-*.whl 2>/dev/null | head -1); \
	if [ -z "$$WHEEL" ]; then \
		echo "no wheel matching $(PROJECT)-*.whl in $(OUTDIR)" >&2; \
		exit 1; \
	fi; \
	$(PIP) install $(PIP_UPGRADE_FLAG) --no-cache-dir $$WHEEL
endif

# sudo umbrella — everything `install-fhs` does (sysusers, tmpfiles,
# venv, systemd, /etc/zoid6). The deploytool `zoid6.prod` sub-target
# resolves to this. Keep this an alias so `make deploy-prod` and
# `make install-fhs` stay interchangeable.
deploy-prod: install-fhs

install-etc:
	sudo install -d -m 0755 -o root -g root $(ETC_DIR)
	sudo install -m 0644 -o root -g root $(FACTORY_DIR)/zoid6.json $(ETC_DIR)/zoid6.json
	sudo chown root:root $(ETC_DIR)/zoid6.json
	@echo "Installed $(ETC_DIR)/zoid6.json from factory defaults"

uninstall-etc:
	-sudo rm -f $(ETC_DIR)/zoid6.json
	@echo "Removed $(ETC_DIR)/zoid6.json"

install-tmpfiles: install-sysusers
	sudo install -m 0644 -o root -g root $(TMPFILES_SRC) $(TMPFILES_DST)
	sudo chown root:root $(TMPFILES_DST)
	sudo systemd-tmpfiles --create
	@echo "Installed $(TMPFILES_DST) and materialized $(ETC_DIR)"

uninstall-tmpfiles:
	-sudo rm -f $(TMPFILES_DST)
	@echo "Removed $(TMPFILES_DST)"

install-sysusers:
	sudo install -m 0644 -o root -g root $(SYSUSERS_SRC) $(SYSUSERS_DST)
	sudo chown root:root $(SYSUSERS_DST)
	sudo systemd-sysusers
	@echo "Created zoid6 user and group via $(SYSUSERS_DST)"

uninstall-sysusers:
	-sudo rm -f $(SYSUSERS_DST)
	@echo "Removed $(SYSUSERS_DST)"

# zoid6 builds on bed, so the bed wheel is installed into the venv too
# (so `import bed` works there).
#
# TODO(verify-install): the `sudo -u <owner> pip install` of the bed wheel
# runs as VENV_OWNER, not the active operator. The verify check has to be
# either (a) re-sudoed to inspect the owner venv, or (b) accepted as a
# blind spot. Compare against `pip show bed`; the wheel just installed is
# a bed wheel, not zoid6. See zoidoffice/src/Makefile's VERIFY_INSTALL for
# the operator-side reference.
install-venv: install-sysusers
	@command -v sudo >/dev/null 2>&1 || { echo "Error: sudo required"; exit 1; }
	@sudo -u $(VENV_OWNER) test -d "$(VENV_DIR)" || sudo -u $(VENV_OWNER) $(PYTHON) -m venv "$(VENV_DIR)"
	sudo -u $(VENV_OWNER) $(VENV_DIR)/bin/pip install --upgrade pip
	sudo -u $(VENV_OWNER) $(VENV_DIR)/bin/pip install $(PIP_UPGRADE_FLAG) --quiet build setuptools wheel
	mkdir -p $(WHEEL_DIR)
	rm -f $(WHEEL_DIR)/*.whl
	$(MAKE) -C $(BED_DIR) version
	$(PYTHON) -m build --no-isolation --wheel --outdir $(WHEEL_DIR) $(BED_DIR)
	sudo -u $(VENV_OWNER) $(VENV_DIR)/bin/pip install $(PIP_UPGRADE_FLAG) --quiet $(WHEEL_DIR)/*.whl
	rm -rf $(WHEEL_DIR)
	@command -v semanage >/dev/null 2>&1 && \
		sudo semanage fcontext -a -t bin_t "$(VENV_DIR)/bin(/.*)?" 2>/dev/null || true
	@command -v restorecon >/dev/null 2>&1 && sudo restorecon -R $(VENV_DIR)/bin/ || true
	@echo "Ensured venv at $(VENV_DIR) (owner: $(VENV_OWNER):$(VENV_GROUP)) with zoid6 + bed"

uninstall-venv:
	-sudo rm -rf $(VENV_DIR)
	@echo "Removed $(VENV_DIR)"

install-systemd:
	sed 's|@VENV_DIR@|$(VENV_DIR)|g' $(UNIT_SRC) | sudo tee $(UNIT_DST) > /dev/null
	sudo chmod 0644 $(UNIT_DST)
	sudo chown root:root $(UNIT_DST)
	sudo systemctl daemon-reload
	@echo "Installed $(UNIT_DST)"

uninstall-systemd:
	-sudo systemctl stop $(PROJECT) 2>/dev/null
	-sudo systemctl disable $(PROJECT) 2>/dev/null
	-sudo rm -f $(UNIT_DST)
	-sudo systemctl daemon-reload
	@echo "Removed $(UNIT_DST)"

restorecon:
	@command -v semanage >/dev/null 2>&1 && \
		sudo semanage fcontext -a -t bin_t "$(VENV_DIR)/bin(/.*)?" 2>/dev/null || true
	sudo restorecon -R $(VENV_DIR)/bin/
	@echo "Relabeled $(VENV_DIR)/bin/ for SELinux"

install-fhs: install-sysusers install-tmpfiles install-venv install-systemd install-etc
	@echo "zoid6 FHS files installed"

uninstall-fhs: uninstall-systemd uninstall-venv uninstall-tmpfiles uninstall-sysusers uninstall-etc
	@echo "zoid6 FHS files uninstalled"

factory-sync:
	install -d -m 0755 $(FACTORY_DIR)
	install -m 0644 -o opencode -g opencode $(PACKAGE)/data/zoid6.json $(FACTORY_DIR)/zoid6.json
	install -m 0644 -o opencode -g opencode $(PACKAGE)/daemon/$(PROJECT).tmpfiles $(FACTORY_DIR)/$(PROJECT).tmpfiles
	install -m 0644 -o opencode -g opencode $(PACKAGE)/daemon/$(PROJECT).sysusers $(FACTORY_DIR)/$(PROJECT).sysusers
	@echo "Synced factory defaults from in-tree sources"


# ----- no-sudo / user-mode install -------------------------------------------
#
# Parallel to the FHS install chain above. Uses $(PREFIX) (default
# ~/.local/zoid6) for everything so a developer can run `make install-user`
# without sudo. Production keeps using install-fhs as before.

reassign-bootstrap-owners:
	@echo "Reassigning public bootstrap SQL functions to role 'zoid6' (creates role if missing) ..."
	$(PYTHON) $(PACKAGE)/reassign_bootstrap_owners.py

# TODO(verify-install): after the batch `pip install` of $(WHEEL_DIR)/*.whl
# (bbsengine6 + bed + casino + zoid6), verify each package's installed
# version against its wheel's METADATA (pip show bbsengine6, bed, casino,
# zoid6). The install is `--quiet`, so silent no-ops are hard to see. See
# zoidoffice/src/Makefile's VERIFY_INSTALL for the reference; this would
# need to loop over the four packages.
#
# casino has an undeclared runtime dep on PIL (lib.py imports it), which is
# not in pyproject.toml, so pillow is installed explicitly below.
install-user:
	@command -v python3 >/dev/null 2>&1 || { echo "Error: python3 not on PATH"; exit 1; }
	@test -n "$$HOME" || { echo "Error: HOME not set"; exit 1; }
	@mkdir -p $(PREFIX) $(ETC_DIR) $(LOG_DIR) $(RUN_DIR) $(DATA_DIR)
	@test -d $(VENV_DIR) || $(PYTHON) -m venv $(VENV_DIR)
	$(VENV_DIR)/bin/pip install --upgrade pip
	$(VENV_DIR)/bin/pip install $(PIP_UPGRADE_FLAG) --quiet build setuptools wheel
	mkdir -p $(WHEEL_DIR)
	rm -f $(WHEEL_DIR)/*.whl
	$(MAKE) -C $(BBSENGINE_DIR) version
	$(PYTHON) -m pip wheel --no-deps --no-build-isolation --no-clean --wheel-dir $(WHEEL_DIR) $(BBSENGINE_DIR)
	$(MAKE) -C $(BED_DIR) version
	$(PYTHON) -m pip wheel --no-deps --no-build-isolation --no-clean --wheel-dir $(WHEEL_DIR) $(BED_DIR)
	$(MAKE) -C $(CASINO_DIR) version
	$(PYTHON) -m pip wheel --no-deps --no-build-isolation --no-clean --wheel-dir $(WHEEL_DIR) $(CASINO_DIR)
	$(MAKE) version
	$(PYTHON) -m pip wheel --no-deps --no-build-isolation --no-clean --wheel-dir $(WHEEL_DIR) $(CURDIR)
	$(VENV_DIR)/bin/pip install $(PIP_UPGRADE_FLAG) --quiet $(WHEEL_DIR)/*.whl
	rm -rf $(WHEEL_DIR)
	$(VENV_DIR)/bin/pip install $(PIP_UPGRADE_FLAG) --quiet pillow
	@test -f $(ETC_DIR)/zoid6.json || install -m 0644 $(PACKAGE)/data/zoid6.json $(ETC_DIR)/zoid6.json
	@test -f $(ETC_DIR)/zoid6.env  || { \
	    { printf 'PREFIX=%s\nVENV_DIR=%s\nLOG_DIR=%s\nRUN_DIR=%s\n' \
	        '$(PREFIX)' '$(VENV_DIR)' '$(LOG_DIR)' '$(RUN_DIR)'; \
	      echo 'BED_DATABASE_USER=$${USER}'; \
	    } > $(ETC_DIR)/zoid6.env; \
	    chmod 0644 $(ETC_DIR)/zoid6.env; }
	@echo "User-mode install complete at $(PREFIX)"
	@echo "Quick start (no sudo):"
	@echo "  make start                     # foreground-daemon via nohup, PID file"
	@echo "  make stop"
	@echo "Or as a systemd user unit (also no sudo):"
	@echo "  make install-systemd-user && systemctl --user enable --now zoid6"

# Autorestart is read from the config ("bed": {"autorestart": true}), not a
# CLI flag; zoid6's argparse does not define --no-autorestart.
# `&` and `echo $!` stay on one line so the PID is captured by the same
# shell that launched the process.
start: install-user
	@test ! -f $(RUN_DIR)/zoid6.pid || { \
	    PID=$$(cat $(RUN_DIR)/zoid6.pid); \
	    if kill -0 $$PID 2>/dev/null; then \
	        echo "zoid6 already running (pid $$PID)"; exit 1; \
	    else \
	        echo "stale pidfile, removing"; rm -f $(RUN_DIR)/zoid6.pid; \
	    fi; }
	nohup $(VENV_DIR)/bin/zoid6 \
	    --config $(ETC_DIR)/zoid6.json \
	    --router zoid6.api.handler.MessageRouter \
	    > $(LOG_DIR)/zoid6.log 2>&1 & echo $$! > $(RUN_DIR)/zoid6.pid
	@echo "zoid6 started (pid $$(cat $(RUN_DIR)/zoid6.pid)) — log: $(LOG_DIR)/zoid6.log"

stop:
	@test -f $(RUN_DIR)/zoid6.pid || { echo "zoid6 not running (no pidfile)"; exit 0; }
	@PID=$$(cat $(RUN_DIR)/zoid6.pid); \
	    if kill -0 $$PID 2>/dev/null; then \
	        kill -TERM $$PID; \
	        for i in 1 2 3 4 5; do kill -0 $$PID 2>/dev/null || break; sleep 1; done; \
	        kill -KILL $$PID 2>/dev/null || true; \
	    fi
	rm -f $(RUN_DIR)/zoid6.pid
	@echo "zoid6 stopped"

status:
	@test -f $(RUN_DIR)/zoid6.pid && PID=$$(cat $(RUN_DIR)/zoid6.pid) || { echo "not running"; exit 1; }
	@kill -0 $$PID 2>/dev/null && echo "running (pid $$PID)" || { echo "stale pidfile (pid $$PID)"; exit 1; }

restart: stop start

# The unit's %h/.local/zoid6 is templated to $(PREFIX), so it matches
# wherever install-user landed (default or overridden PREFIX).
#
# daemon-reload is best-effort: it only matters when a user systemd is
# running (interactive login). Headless servers, or containers without
# $DBUS_SESSION_BUS_ADDRESS, skip it.
install-systemd-user: install-user
	@mkdir -p $(SYSTEMD_USER_DIR)
	sed 's|%h/\.local/zoid6|$(PREFIX)|g' $(PACKAGE)/daemon/zoid6-user.service \
	    > $(SYSTEMD_USER_DIR)/zoid6.service
	chmod 0644 $(SYSTEMD_USER_DIR)/zoid6.service
	-systemctl --user daemon-reload 2>/dev/null || true
	@echo "Installed $(SYSTEMD_USER_DIR)/zoid6.service (user unit)"
	@echo "Enable + start:  systemctl --user enable --now zoid6"
	@echo "Boot persistence: loginctl enable-linger $$USER  (one-time, needs root)"

uninstall-systemd-user:
	-systemctl --user disable --now zoid6 2>/dev/null || true
	-rm -f $(SYSTEMD_USER_DIR)/zoid6.service
	-systemctl --user daemon-reload 2>/dev/null || true
	@echo "Removed $(SYSTEMD_USER_DIR)/zoid6.service"

uninstall-user:
	-rm -rf $(PREFIX)
	@echo "Removed $(PREFIX)"
