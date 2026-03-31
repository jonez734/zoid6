export HOST = merlin
export PROJECT = zoidtechnologies.com

export PROD = $(HOST):/srv/www/vhosts/$(PROJECT)/
export STAGE = /srv/www/vhosts/$(PROJECT)/

export SCSSLOADPATH = --load-path $(HOME)/projects/zoid6/scss/ --load-path $(HOME)/projects/$(PROJECT)/skin/scss/ --load-path $(HOME)/projects/bbsengine6/skin/scss/
export SCSS = sass --line-numbers --stop-on-error --trace --style expanded --sourcemap=none $(SCSSLOADPATH)

export RSYNC = rsync --chmod=Dg=rwxs,Fgu=rw,Fo=r --no-times --verbose \
	--archive --update --backup --recursive \
	--human-readable --checksum --rsh=ssh \
	--delete-after --mkpath --exclude '.well-known' --exclude '*~' --exclude 'templates_c'

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
