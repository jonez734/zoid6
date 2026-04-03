all:

.scss.css:
	sass --load-path /home/opencode/data/work/zoid6/shared/skin/scss/ \
	     --load-path /home/opencode/data/work/bbsengine6/skin/scss/ \
	     --load-path /home/opencode/data/work/zoid6/sites/static/skin/scss \
	     --sourcemap=none --stop-on-error --trace --style expanded $< $@

stage: 
	sass --load-path /home/opencode/data/work/zoid6/shared/skin/scss/ \
	     --load-path /home/opencode/data/work/bbsengine6/skin/scss/ \
	     --load-path /home/opencode/data/work/zoid6/sites/static/skin/scss \
	     --sourcemap=none --stop-on-error --trace --style expanded \
	     --update .:/srv/www/vhosts/static.zoidtechnologies.com/html/skin/css/

clean:
	-rm *~ *.css *.css.map

.SUFFIXES: .scss .css

.PHONY: clean stage
