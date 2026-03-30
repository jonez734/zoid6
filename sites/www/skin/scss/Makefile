scssfiles := $(wildcard *.scss)
cssfiles := $(scssfiles:.scss=.css)

all:

.scss.css:
	$(SCSS) $< $@

stage:
	$(SCSS) --update --watch .:$(DOCROOT)skin/css/

clean:
	-rm *~ *.css *.css.map

.SUFFIXES: .scss .css
