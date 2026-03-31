all:

.scss.css:
	$(SCSS) -I ../../shared/skin/scss $< $@

stage:
	$(SCSS) --update --watch -I ../../shared/skin/scss .:$(STAGE)skin/css/

clean:
	-rm *~ *.css *.css.map

.SUFFIXES: .scss .css