export HOST = merlin
export PROJECT = zoid6

export STAGE = /srv/www/zoid6/
export PROD = $(HOST):$(STAGE)

export STAGEDOCROOT = /srv/www/vhosts/zoidtechnologies.com/html/
export PRODDOCROOT = $(HOST):$(STAGEDOCROOT)

export SCSSLOADPATH = --load-path /home/opencode/data/work/zoid6/shared/skin/scss/ \
                      --load-path /home/opencode/data/work/bbsengine6/skin/scss/

export SCSS = sass $(SCSSLOADPATH) --sourcemap=none --stop-on-error --trace --style expanded --update

export RSYNC = rsync --chmod=Dg=rwxs,Fgu=rw,Fo=r --no-times --verbose --archive --update --backup --recursive --human-readable --checksum --rsh=ssh --mkpath # --delete-after

export PYTHON ?= python3
VERSION = $(shell date +%Y%m%d%H%M)

all:

clean:
	-rm *~
	$(MAKE) -C php clean
	$(MAKE) -C shared clean
	$(MAKE) -C markdown clean

prod: shared markdown parsedown-deploy
#	python -m areyousure || (echo "aborted $$?"; exit 1)
	mkdir -p $(STAGE)
	$(MAKE) -C php stage
	$(MAKE) -C smarty stage
	$(MAKE) -C markdown stage
	$(RSYNC) $(STAGE) $(PROD)

shared:
	$(MAKE) -C shared stage
	$(RSYNC) --delete $(STAGEDOCROOT)shared/ $(PRODDOCROOT)shared/

backup:
	rsync --recursive --verbose --exclude=.venv . /run/media/jam/AEAB-CF37/projects/$(PROJECT)/

.PHONY: shared backup


markdown:
	$(MAKE) -C markdown stage
	$(RSYNC) --dry-run $(STAGE)markdown $(HOST):$(STAGE)

parsedown-deploy:
	mkdir -p /srv/www/markdown/
	$(RSYNC) /home/opencode/data/work/bbsengine6/vendor/erusev/parsedown/Parsedown.php /srv/www/markdown/
	$(RSYNC) /home/opencode/data/work/bbsengine6/vendor/erusev/parsedown-extra/ParsedownExtra.php /srv/www/markdown/
	$(RSYNC) /srv/www/markdown/ $(HOST):$(STAGE)

php-deploy:
	$(MAKE) -C php stage

.PHONY: markdown parsedown-deploy php-deploy deploy deploy-www deploy-tui

deploy:
	$(MAKE) -C shared stage
	$(MAKE) php-deploy
	mkdir -p $(STAGE)smarty/
	$(RSYNC) smarty/*.php $(STAGE)smarty/
	$(RSYNC) --delete $(STAGEDOCROOT)shared/ $(PRODDOCROOT)shared/
	$(RSYNC) $(STAGE) $(PROD)

deploy-www:
	$(MAKE) -C sites/www prod

deploy-tui:
	$(MAKE) -C src deploy-tui

.PHONY: version build commit-version

version:
	$(MAKE) -C src version

build: version
	$(MAKE) -C src build

commit-version:
	git add src/$(PROJECT)/_version.py
	git diff --cached --quiet || git commit -m "Bump $(PROJECT) version to $(VERSION)"
