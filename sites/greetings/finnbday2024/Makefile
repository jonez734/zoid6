#export HOST = merlin
#export PROD = $(HOST):/srv/www/vhosts/greetings.zoidtechnologies.com/
#export PRODDOCROOT = $(PROD)html/
#export STAGE = /srv/staging/greetings.zoidtechnologies.com/
#export STAGEDOCROOT = $(STAGEPROD)html/

all:

clean:
	-rm *~
	-$(MAKE) -C skin clean
	-$(MAKE) -C js clean

install:	export DOCROOT = $(STAGEDOCROOT)
#install:	export STAGE = $(STAGE)
install:
	mkdir -p $(STAGEDOCROOT)
	mkdir -p $(STAGE)templates_c/

	$(RSYNC) finnbday2022.html $(STAGEDOCROOT)
#	$(MAKE) -C php install
	$(MAKE) -C skin install
	#-$(MAKE) -C smarty install
	-$(MAKE) -C js install
	#-$(MAKE) -C fonts install
	#$(RSYNC) $(STAGE) $(PROD)

.PHONY: install
