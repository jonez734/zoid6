all:

clean:
	-rm *~

install:
	mkdir -p $(DOCROOT)skin/art/
#	-$(installfile) *.png *.gif *.jpg $(DOCROOT)skin/art/
	$(RSYNC) --exclude 'Thumbs.db' --exclude 'Makefile' . $(DOCROOT)skin/art/
