all:

.scss.css:
	$(SCSS) $< $@

stage:
	$(SCSS) --update --watch .:$(SHAREDSTAGE)skin/css/

clean:
	-rm *~ *.css *.css.map

.SUFFIXES: .scss .css
