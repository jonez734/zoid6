export HOST = merlin
export PROJECT = zoid6

export STAGE = /srv/www/zoid6/
export PROD = $(HOST):$(STAGE)

export STAGEDOCROOT = /srv/www/vhosts/zoidtechnologies.com/html/
export PRODDOCROOT = $(HOST):$(STAGEDOCROOT)

export SCSSLOADPATH = --load-path /home/opencode/data/work/zoid6/shared/skin/scss/ \
                      --load-path /home/opencode/data/work/bbsengine6/skin/scss/

export SCSS = sass $(SCSSLOADPATH) --sourcemap=none --stop-on-error --trace --style expanded --update

export RSYNC = rsync --chmod=Dg=rwxs,Fgu=rw,Fo=r --no-times --verbose \
	--archive --update --backup --recursive \
	--human-readable --checksum --rsh=ssh \
	--delete-after --mkpath

all:

clean:
	-rm *~
	$(MAKE) -C php clean
	$(MAKE) -C shared clean

prod: shared
#	python -m areyousure || (echo "aborted $$?"; exit 1)
	mkdir -p $(STAGE)
	$(MAKE) -C php stage
	$(MAKE) -C smarty stage
	$(RSYNC) $(STAGE) $(PROD)

shared:
	$(MAKE) -C shared stage
	$(RSYNC) $(STAGEDOCROOT)shared/ $(PRODDOCROOT)shared/

backup:
	rsync --recursive --verbose --exclude=.venv . /run/media/jam/AEAB-CF37/projects/$(PROJECT)/

.PHONY: shared backup


markdown:
	$(RSYNC) markdown/ $(HOST):/srv/www/zoid6/markdown/

.PHONY: markdown
