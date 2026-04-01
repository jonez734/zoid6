all:

.scss.css:
	sass --load-path ../../../../shared/skin/scss/ \
	     --load-path ../../../../scss/ \
	     --load-path $(CURDIR) \
	     --sourcemap=none --stop-on-error --trace --style expanded $< $@

stage: 
	sass --load-path ../../../../shared/skin/scss/ \
	     --load-path ../../../../scss/ \
	     --load-path $(CURDIR) \
	     --sourcemap=none --stop-on-error --trace --style expanded \
	     --update .:$(STAGEDOCROOT)skin/css/

clean:
	-rm *~ *.css *.css.map

.SUFFIXES: .scss .css

.PHONY: clean stage
