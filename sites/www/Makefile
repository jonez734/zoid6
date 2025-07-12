export HOST = merlin
export PROJECT = theaiconsensus.com

export PROD = $(HOST):/srv/www/vhosts/$(PROJECT)/
export STAGE = /srv/www/vhosts/$(PROJECT)/

#export STATICPROD = $(HOST):/srv/www/vhosts/static.zoidtechnologies.com/
#export STATICSTAGE = /srv/www/vhosts/static.zoidtechnologies.com/

export SCSSLOADPATH = --load-path $(HOME)/projects/$(PROJECT)/skin/scss/ --load-path $(HOME)/projects/bbsengine6/skin/scss/
export SCSS = sass --line-numbers --stop-on-error --trace --style expanded --sourcemap=none $(SCSSLOADPATH)

export RSYNC = rsync --chmod=Dg=rwxs,Fgu=rw,Fo=r --no-times --verbose \
	--archive --update --backup --recursive \
	--human-readable --checksum --rsh=ssh \
	--delete-after --mkpath --exclude '.well-known' --exclude '*~' --exclude 'templates_c'

export ENGINESTAGEDOCROOT = /srv/www/vhosts/$(PROJECT)/html/engine/
export STATICSTAGEDOCROOT = /srv/www/vhosts/($PROJECT)/html/static/

all:

clean:
	-rm *~
	$(MAKE) -C php clean
	$(MAKE) -C skin clean
	$(MAKE) -C engine clean

prod:
	mkdir -p $(STAGE)
	$(MAKE) -C php stage
	$(MAKE) -C smarty stage
	$(MAKE) -C skin stage
	$(MAKE) -C engine stage
	$(MAKE) -C static stage
	$(RSYNC) config-prod.php $(STAGE)html/config.php
	$(RSYNC) htaccess-prod $(STAGE)html/.htaccess
	$(RSYNC) $(STAGE) $(PROD)
