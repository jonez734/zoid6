export HOST = merlin
export PROJECT = zoid6

export STAGE = /srv/www/zoid6/
export PROD = $(HOST):$(STAGE)

export STAGEDOCROOT = /srv/www/vhosts/zoidtechnologies.com/html/
export PRODDOCROOT = $(HOST):$(STAGEDOCROOT)

export SCSSLOADPATH = --load-path /home/opencode/data/work/zoid6/shared/skin/scss/ \
                      --load-path /home/opencode/data/work/bbsengine6/skin/scss/

export SCSS = sass $(SCSSLOADPATH) --sourcemap=none --stop-on-error --trace --style expanded --update

export RSYNC = rsync --chmod=Dg=rwxs,Fgu=rw,Fo=r --verbose --archive --times --no-group --update --backup --recursive --human-readable --checksum --rsh=ssh --mkpath # --delete-after

export PYTHON ?= python3
VERSION = $(shell date +%Y%m%d%H%M)

export VENV_DIR ?= /var/lib/zoid6/venv
export VENV_USER ?= zoid6
export VENV_GROUP ?= zoid6

export BED_DIR ?= $(CURDIR)/../bed
export WHEEL_DIR ?= /tmp/zoid6-bed-$$

all:

clean:
	-rm *~
	$(MAKE) -C php clean
	$(MAKE) -C shared clean
	$(MAKE) -C markdown clean

# stage: build the shared chrome, php, smarty, and markdown trees locally
# under $(STAGE) and $(STAGEDOCROOT). Nothing is pushed to $(HOST).
stage: shared-stage
	mkdir -p $(STAGE)
	$(MAKE) -C smarty stage
	$(MAKE) -C markdown stage

# prod: stage, then push the zoid6 tree and shared chrome to $(HOST).
prod: stage parsedown-deploy php-deploy-prod
	$(RSYNC) $(STAGE) $(PROD)
	$(RSYNC) --delete $(STAGEDOCROOT)shared/ $(PRODDOCROOT)shared/

# shared: stage the shared chrome and push it to $(HOST). Named target
# (no deploy- prefix) because deploytool calls it directly.
shared: shared-stage
	$(RSYNC) --delete $(STAGEDOCROOT)shared/ $(PRODDOCROOT)shared/

shared-stage:
	$(MAKE) -C shared stage

backup:
	rsync --recursive --verbose --exclude=.venv . /run/media/jam/AEAB-CF37/projects/$(PROJECT)/

.PHONY: stage prod shared shared-stage backup

# markdown: stage the markdown library locally. The merlin push for it is
# parsedown-deploy, which runs as part of prod.
markdown: markdown-stage

markdown-stage:
	$(MAKE) -C markdown stage

parsedown-deploy:
	mkdir -p /srv/www/markdown/
	$(RSYNC) /home/opencode/data/work/bbsengine6/vendor/erusev/parsedown/Parsedown.php /srv/www/markdown/
	$(RSYNC) /home/opencode/data/work/bbsengine6/vendor/erusev/parsedown-extra/ParsedownExtra.php /srv/www/markdown/
	$(RSYNC) /srv/www/markdown/ $(HOST):$(STAGE)

php-deploy:
	$(MAKE) -C php stage

php-deploy-prod: php-deploy
	$(RSYNC) $(STAGE)php/ $(PROD)php/

.PHONY: markdown parsedown-deploy php-deploy php-deploy-prod deploy deploy-www deploy-tui deploy-prod

# deploy is the name older docs and muscle memory use; same as prod.
deploy: prod

deploy-www:
	$(MAKE) -C sites/www prod

deploy-tui:
	$(MAKE) -C src deploy-tui

deploy-prod:
	$(MAKE) -C src deploy-prod VENV_DIR="$(VENV_DIR)" VENV_USER="$(VENV_USER)" VENV_GROUP="$(VENV_GROUP)" BED_DIR="$(BED_DIR)" WHEEL_DIR="$(WHEEL_DIR)"

.PHONY: install-venv uninstall-venv install-fhs uninstall-fhs

install-venv:
	$(MAKE) -C src install-venv VENV_DIR="$(VENV_DIR)" VENV_USER="$(VENV_USER)" VENV_GROUP="$(VENV_GROUP)" BED_DIR="$(BED_DIR)" WHEEL_DIR="$(WHEEL_DIR)"

uninstall-venv:
	$(MAKE) -C src uninstall-venv VENV_DIR="$(VENV_DIR)"

install-fhs:
	$(MAKE) -C src install-fhs VENV_DIR="$(VENV_DIR)" VENV_USER="$(VENV_USER)" VENV_GROUP="$(VENV_GROUP)" BED_DIR="$(BED_DIR)" WHEEL_DIR="$(WHEEL_DIR)"

uninstall-fhs:
	$(MAKE) -C src uninstall-fhs VENV_DIR="$(VENV_DIR)"

.PHONY: version build commit-version

version:
	$(MAKE) -C src version

build: version
	$(MAKE) -C src build

commit-version:
	git add src/$(PROJECT)/_version.py
	git diff --cached --quiet || git commit -m "Bump $(PROJECT) version to $(VERSION)"
