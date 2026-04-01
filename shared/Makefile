export PROD = $(HOST):/srv/www/zoid6/shared/
export STAGE = /srv/www/zoid6/shared/

all:

clean:
	-rm *~
	$(MAKE) -C skin clean

stage:
	$(MAKE) -C skin stage

prod:
	$(MAKE) -C skin stage
	$(RSYNC) $(SHAREDSTAGE) $(SHAREDPROD)
