export SCSS = sass --line-numbers --stop-on-error --trace --style expanded --sourcemap=none --load-path $(SCSSLOADPATH)

all:

clean:
	-rm *~
	$(MAKE) -C css clean
	$(MAKE) -C tmpl clean
	$(MAKE) -C art clean
	$(MAKE) -C scss clean

install:
	mkdir -p $(DOCROOT)skin/tmpl/
	mkdir -p $(DOCROOT)skin/scss/
	mkdir -p $(DOCROOT)skin/art/
	$(MAKE) -C scss install
	$(MAKE) -C tmpl install
	$(MAKE) -C art install
