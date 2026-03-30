scssfiles := $(wildcard *.scss)
cssfiles := $(scssfiles:.scss=.css)

SCSS = sass --stop-on-error --trace --style expanded --sourcemap=none --load-path ../../../../../common/

all:

.scss.css:
	$(SCSS) $< $@

install:
	$(SCSS) --update --watch .:$(STAGEDOCROOT)skin/css/

clean:
	-rm *~ *.css *.css.map

.SUFFIXES: .scss .css

.PHONY: install
