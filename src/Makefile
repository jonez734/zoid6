PROJECT = zoid6
OUTDIR = /srv/repo/$(PROJECT)/
VERSION := $(shell date +%Y%m%d%H%M)

PYTHON ?= python3
PIP ?= $(PYTHON) -m pip
PYTEST ?= $(PYTHON) -m pytest

SRC := .
PACKAGE := zoid6

FACTORY_DIR = $(CURDIR)/../usr/share/factory/etc/$(PROJECT)
ETC_DIR = /etc/$(PROJECT)
TMPFILES_SRC = $(PACKAGE)/daemon/$(PROJECT).tmpfiles
TMPFILES_DST = /usr/lib/tmpfiles.d/$(PROJECT).conf
SYSUSERS_SRC = $(PACKAGE)/daemon/$(PROJECT).sysusers
SYSUSERS_DST = /usr/lib/sysusers.d/$(PROJECT).conf
UNIT_SRC = $(PACKAGE)/daemon/$(PROJECT).service
UNIT_DST = /usr/lib/systemd/system/$(PROJECT).service

VENV_DIR ?= /var/lib/zoid6/venv
VENV_OWNER ?= zoid6
VENV_GROUP ?= zoid6

BED_DIR ?= $(CURDIR)/../bed
WHEEL_DIR ?= /tmp/zoid6-bed-$$

.PHONY: all help install install-dev test test-cov test-verbose lint clean uninstall egg-info
.PHONY: build version rename-sdist sign release
.PHONY: install-etc install-tmpfiles install-fhs uninstall-etc uninstall-tmpfiles factory-sync
.PHONY: install-sysusers uninstall-sysusers
.PHONY: install-venv uninstall-venv restorecon
.PHONY: install-systemd uninstall-systemd uninstall-fhs
.PHONY: deploy-tui

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
	@echo "  deploy-tui    install (editable); does NOT build/sign artifacts"

install: version
	$(PIP) install --no-cache-dir -e $(SRC)

install-dev: install
	$(PIP) install pytest pytest-cov

test:
	cd $(SRC) && $(PYTEST) $(PACKAGE)/tests -v

test-verbose:
	cd $(SRC) && $(PYTEST) $(PACKAGE)/tests -vv

test-cov:
	cd $(SRC) && $(PYTEST) $(PACKAGE)/tests --cov=$(PACKAGE) --cov-report=term-missing

lint:
	$(PYTHON) -m pyflakes $(PACKAGE) || true

egg-info:
	$(PIP) install -e $(SRC) --no-deps --force-reinstall --no-build-isolation

uninstall:
	$(PIP) uninstall -y $(PACKAGE)

clean:
	-rm -rf build dist
	-rm -rf *.egg-info
	-find . -type d -name __pycache__ -exec rm -rf {} +
	-find . -type d -name .pytest_cache -exec rm -rf {} +
	-find . -type d -name .ruff_cache -exec rm -rf {} +
	-find . -type d -name .mypy_cache -exec rm -rf {} +

version:
	@echo '__version__ = "0.0.1.dev$(VERSION)"' > $(PROJECT)/_version.py
	@echo '__datestamp__ = "$(VERSION)"' >> $(PROJECT)/_version.py
	@echo '__githash__ = "'`git log -1 --format='%H' 2>/dev/null | cut -c 1-16`'"' >> $(PROJECT)/_version.py
	@cat $(PROJECT)/_version.py

build: version
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

deploy-tui: install

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

install-venv: install-sysusers
	@command -v sudo >/dev/null 2>&1 || { echo "Error: sudo required"; exit 1; }
	@sudo -u $(VENV_OWNER) test -d "$(VENV_DIR)" || sudo -u $(VENV_OWNER) $(PYTHON) -m venv "$(VENV_DIR)"
	sudo -u $(VENV_OWNER) $(VENV_DIR)/bin/pip install --upgrade pip
	sudo -u $(VENV_OWNER) $(VENV_DIR)/bin/pip install --quiet build setuptools wheel
	# zoid6 builds on bed; install the bed wheel so `import bed` works inside this venv.
	mkdir -p $(WHEEL_DIR)
	rm -f $(WHEEL_DIR)/*.whl
	$(MAKE) -C $(BED_DIR) version
	$(PYTHON) -m build --no-isolation --wheel --outdir $(WHEEL_DIR) $(BED_DIR)
	sudo -u $(VENV_OWNER) $(VENV_DIR)/bin/pip install --quiet $(WHEEL_DIR)/*.whl
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
