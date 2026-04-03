export SHAREDSTAGE = /srv/www/zoid6/shared/

SCSS := sass --sourcemap=none --stop-on-error --trace --style expanded \
	--load-path /home/opencode/data/work/zoid6/shared/skin/scss/ \
	--load-path /home/opencode/data/work/bbsengine6/skin/scss/ \
	--load-path $(CURDIR)

scssfiles := $(wildcard *.scss)
cssfiles := $(scssfiles:.scss=.css)

all: $(cssfiles)

%.css: %.scss
	$(SCSS) $< $@

stage:
	$(SCSS) --update .:$(SHAREDSTAGE)/skin/css/

clean:
	-rm *~ *.css *.css.map

.PHONY: all clean stage
