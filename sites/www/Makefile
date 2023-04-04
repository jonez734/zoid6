export HOST = merlin

export PROJECT = zoidweb6

export PROD = $(HOST):/srv/www/vhosts/www.zoidtechnologies.com/
export PRODDOCROOT = $(PROD)html/
export STAGEPROD = /srv/www/vhosts/www.zoidtechnologies.com/
export STAGEPRODDOCROOT = $(STAGEPROD)html/

# @since 20230404
export SCSSLOADPATH = --load-path $(HOME)/projects/zoidweb6/skin/scss/ --load-path $(HOME)/projects/bbsengine6/skin/scss/

#export STAGEPRODSSLDOCROOT = $(STAGEPROD)html/

export datestamp = $(shell date +%Y%m%d-%H%M)
export archivename = $(PROJECT)-$(datestamp)-$(USER)
export installfile = $(RSYNC)
#export installfile = install --mode=0660

export RSYNC = rsync --chmod=Dg=rwxs,Fgu=rw,Fo=r --no-times --verbose \
	--archive --update --backup --recursive \
	--human-readable --checksum --rsh=ssh \
	--no-owner --no-group \
	--delete-after \
	--links \
	--mkpath \
	--exclude '.~lock*' \
	--exclude '*~'

all:

clean:
	-rm *~
	$(MAKE) -C skin clean
	$(MAKE) -C php clean
	$(MAKE) -C smarty clean
	$(MAKE) -C js clean

prod:	export DOCROOT = $(STAGEPRODDOCROOT)
prod:	export STAGE = $(STAGEPROD)
prod:
	python -m areyousure || (echo "aborted $$?"; exit 1)
	mkdir -p $(DOCROOT)
	mkdir -p $(STAGE)templates_c/
	mkdir -p $(DOCROOT)captchas/

	$(MAKE) -C php install
	$(MAKE) -C skin install
	$(MAKE) -C smarty install
	$(MAKE) -C js install
	$(installfile) config-prod.php $(DOCROOT)config.php
	#$(installfile) ../private/htpasswd-prod $(DOCROOT).htpasswd
	$(installfile) htaccess-prod $(DOCROOT).htaccess
	$(installfile) google067c1cb56376e896.html $(DOCROOT)google067c1cb56376e896.html
	$(RSYNC) $(STAGE) $(PROD)

.PHONY: prod
