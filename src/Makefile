PROJECT = zoid6
OUTDIR = /srv/repo/$(PROJECT)/
VERSION = $(shell date +%Y%m%d%H%M)

PYTHON ?= python3
PIP ?= $(PYTHON) -m pip
PYTEST ?= $(PYTHON) -m pytest

SRC := .
PACKAGE := zoid6

.PHONY: all help install install-dev test test-cov test-verbose lint clean uninstall egg-info
.PHONY: build version rename-sdist sign release

all: help

help:
	@echo "zoid6 - Unified BBS module router"
	@echo ""
	@echo "Targets:"
	@echo "  install       Install zoid6 in editable mode (pip install -e .)"
	@echo "  install-dev   Install zoid6 with test extras"
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

install:
	$(PIP) install -e $(SRC)

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
	@echo 'githash = "'`git log -1 --format='%H' 2>/dev/null | cut -c 1-16`'"' >> $(PROJECT)/_version.py
	@echo 'datestamp = "$(VERSION)"' >> $(PROJECT)/_version.py
	@cat $(PROJECT)/_version.py

build:
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
