# zoid6/scss Makefile
# Compile SCSS files to verify they work

SCSS := sass --sourcemap=none --stop-on-error --trace --style expanded

scssfiles := $(wildcard *.scss)
cssfiles := $(scssfiles:.scss=.css)

all: $(cssfiles)

.scss.css:
	$(SCSS) $< $@

stage:
	$(SCSS) --update .:$(STAGE)skin/css/

clean:
	-rm *~ *.css *.css.map

.PHONY: all clean stage
