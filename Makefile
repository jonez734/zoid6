export HOST = merlin
export PROJECT = zoid6

export PROD = $(HOST):/srv/www/$(PROJECT)/
export STAGE = /srv/www/$(PROJECT)/

export SHAREDPROD = $(HOST):/srv/www/vhosts/zoidtechnologies.com/html/shared/
export SHAREDSTAGE = /srv/www/zoid6/shared/

export SHAREDSTAGEDOCROOT = /srv/www/zoid6/shared/skin/css/
export SHAREDPRODDOCROOT = $(HOST):/srv/www/vhosts/zoidtechnologies.com/html/shared/skin/css/

export STATICPROD = $(HOST):/srv/www/vhosts/static.zoidtechnologies.com/
export STATICSTAGE = /srv/www/vhosts/static.zoidtechnologies.com/

export SCSSLOADPATH = --load-path /home/opencode/data/work/zoid6/shared/skin/scss/ \
	--load-path /home/opencode/data/work/bbsengine6/skin/scss/
export SCSS = sass --line-numbers --stop-on-error --trace --style expanded --sourcemap=none $(SCSSLOADPATH)

export RSYNC = rsync --chmod=Dg=rwxs,Fgu=rw,Fo=r --no-times --verbose \
	--archive --update --backup --recursive \
	--human-readable --checksum --rsh=ssh \
	--delete-after --mkpath

all:

clean:
	-rm *~
	$(MAKE) -C php clean
	$(MAKE) -C skin clean
	$(MAKE) -C shared clean

prod:
#	python -m areyousure || (echo "aborted $$?"; exit 1)
	mkdir -p $(STAGE)
	$(MAKE) -C php stage
	$(MAKE) -C smarty stage
	$(MAKE) -C skin stage
	$(MAKE) -C scss stage
	$(MAKE) -C shared stage
#	$(MAKE) -C js stage
#	$(MAKE) -C sites/www/ stage
#	$(RSYNC) htaccess-prod $(STATICSTAGE)html/.htaccess
	$(RSYNC) $(STAGE) $(PROD)

shared:
	$(MAKE) -C shared stage
#	$(MAKE) -C scss stage
	$(RSYNC) $(SHAREDSTAGE) $(SHAREDPROD)
	$(RSYNC) $(SHAREDSTAGEDOCROOT) $(SHAREDPRODDOCROOT)

backup:
	rsync --recursive --verbose --exclude=.venv . /run/media/jam/AEAB-CF37/projects/$(PROJECT)/

.PHONY: shared
