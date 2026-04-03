export HOST = merlin
export PROJECT = zoidtechnologies.com

export PROD = $(HOST):/srv/www/vhosts/$(PROJECT)/
export STAGE = /srv/www/vhosts/$(PROJECT)/

export datestamp = $(shell date +%Y%m%d-%H%M)
export archivename = $(PROJECT)-$(datestamp)-$(USER)
export installfile = install --mode=0660

export SCSSLOADPATH = --load-path /home/opencode/data/work/zoid6/shared/skin/scss/ \
                      --load-path /home/opencode/data/work/bbsengine6/skin/scss/ \
                      --load-path /home/opencode/data/work/zoid6/sites/www/skin/scss/
export SCSS = sass $(SCSSLOADPATH) --sourcemap=none --stop-on-error --trace --style expanded --update

export RSYNC = rsync --delete-after --chmod=Dg=rwxs,Fgu=rw,Fo=r --verbose \
	--archive --update --backup --recursive \
	--human-readable --rsh=ssh \
	--no-owner --no-group --checksum --mkpath

export DOCROOT = $(STAGE)html/

all:

clean:
	-rm *~
	$(MAKE) -C php clean
	$(MAKE) -C skin clean
	$(MAKE) -C engine clean

prod:
	mkdir -p $(DOCROOT)
	$(MAKE) -C php stage
	$(MAKE) -C smarty stage
	$(MAKE) -C skin stage
	$(RSYNC) config-prod.php $(DOCROOT)config.php
	$(RSYNC) htaccess-prod $(DOCROOT).htaccess
	$(RSYNC) $(STAGE) $(PROD)
