export HOST = merlin
export PROJECT = engine

export PROD = $(HOST):/srv/www/vhosts/zoidtechnologies.com/
export PRODDOCROOT = $(PROD)html/$(PROJECT)/

export STAGE = /srv/www/vhosts/zoidtechnologies.com/
export STAGEDOCROOT = $(STAGE)html/$(PROJECT)/

export datestamp = $(shell date +%Y%m%d-%H%M)
export archivename = $(PROJECT)-$(datestamp)-$(USER)

export RSYNC = rsync --chmod=Dg=rwxs,Fgu=rw,Fo=r --verbose \
	--mkpath \
	--archive --update --backup --recursive \
	--human-readable --checksum --rsh=ssh \
	--no-owner \
	--delete-after \
	--exclude='*~' \
	--exclude='.~lock*' \
	--exclude='html/captchas*' \
	--exclude='html/usage*' \
	--exclude='443' \
	--exclude 'skin/fonts/' \
	--exclude 'js/tinymce/' \
	--exclude 'templates_c/'

all:

clean:
	-rm *~
	$(MAKE) -C skin clean
	$(MAKE) -C php clean
#	$(MAKE) -C smarty clean
	$(MAKE) -C js clean

prod:
	mkdir -p $(STAGEDOCROOT)
#	mkdir -p $(STAGE)templates_c/

	$(MAKE) -C php stage
	$(MAKE) -C skin stage
	$(MAKE) -C js stage
	$(RSYNC) config-prod.php $(STAGEDOCROOT)config.php
	$(RSYNC) htaccess-prod $(STAGEDOCROOT).htaccess
	$(RSYNC) $(STAGE) $(PROD)

.PHONY: prod clean
 