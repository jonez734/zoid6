PYTHON ?= python3
PIP ?= $(PYTHON) -m pip
PYTEST ?= $(PYTHON) -m pytest

SRC := .
PACKAGE := zoid6

.PHONY: all help install install-dev test test-cov test-verbose lint clean uninstall egg-info

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
