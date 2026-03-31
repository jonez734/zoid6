export HOST = merlin
export PROJECT = static
export PROD = $(HOST):/srv/www/vhosts/zoidtechnologies.com/html/$(PROJECT)/
export STAGE = /srv/www/vhosts/zoidtechnologies.com/html/$(PROJECT)/

export PRODDOCROOT = $(HOST):/srv/www/vhosts/zoidtechnologies.com/html/$(PROJECT)/
export STAGEDOCROOT = /srv/www/vhosts/zoidtechnologies.com/html/$(PROJECT)/

export SCSSLOADPATH = --load-path /home/opencode/data/work/zoid6/skin/scss/ --load-path $(HOME)/projects/zoid6/skin/scss/ --load-path $(HOME)/projects/bbsengine6/skin/scss/
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
	# python -m areyousure || (echo "aborted $$?"; exit 1)
	mkdir -p $(STAGE)templates_c/

	$(MAKE) -C js stage
	$(MAKE) -C skin stage
	$(RSYNC) htaccess-prod $(STAGEDOCROOT).htaccess
	$(RSYNC) $(STAGE) $(PROD)
