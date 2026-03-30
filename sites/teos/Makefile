export HOST = merlin
export PROJECT = teos
export PROD = $(HOST):/srv/www/vhosts/$(PROJECT).zoidtechnologies.com/
export STAGE = /srv/www/vhosts/$(PROJECT).zoidtechnologies.com/

export SCSSLOADPATH = --load-path $(HOME)/projects/zoid6/skin/scss/ --load-path $(HOME)/projects/bbsengine6/skin/scss/
export SCSS = sass --line-numbers --stop-on-error --trace --style expanded --sourcemap=none $(SCSSLOADPATH)

export RSYNC = rsync --chmod=Dg=rwxs,Fgu=rw,Fo=r --no-times --verbose \
	--archive --update --backup --recursive \
	--human-readable --checksum --rsh=ssh \
	--delete-after --mkpath

all:

clean:
	-rm *~
	$(MAKE) -C js clean
	$(MAKE) -C skin clean

prod:
	python -m areyousure || (echo "aborted $$?"; exit 1)
	mkdir -p $(STAGE)templates_c/

	-$(MAKE) -C js stage
	$(MAKE) -C skin stage
	$(RSYNC) htaccess-prod $(STAGE)html/.htaccess
	$(RSYNC) $(STAGE) $(PROD)
