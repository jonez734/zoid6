# SCSS = sass --stop-on-error --trace --style expanded

all:

.scss.css:
	$(SCSS) $< $@

install: 
	$(SCSS) --update --watch .:$(STATICSTAGEDOCROOT)skin/css/

clean:
	-rm *~ *.css *.css.map

.SUFFIXES: .scss .css

.PHONY: clean install
