all:

.scss.css:
	sass --load-path /home/opencode/data/work/zoid6/shared/skin/scss/ \
	     --load-path /home/opencode/data/work/zoid6/scss/ \
	     --sourcemap=none --stop-on-error --trace --style expanded $< $@

stage:
	sass --load-path /home/opencode/data/work/zoid6/shared/skin/scss/ \
	     --load-path /home/opencode/data/work/zoid6/scss/ \
	     --sourcemap=none --stop-on-error --trace --style expanded \
	     --update .:$(SHAREDSTAGE)skin/css/

clean:
	-rm *~ *.css *.css.map

.SUFFIXES: .scss .css

.PHONY: clean stage
