all:

.scss.css:
	$(SCSS) $< $@

stage: 
	$(SCSS) --update --watch .:$(STAGEDOCROOT)skin/css/

clean:
	-rm *~ *.css *.css.map

.SUFFIXES: .scss .css

.PHONY: clean install
