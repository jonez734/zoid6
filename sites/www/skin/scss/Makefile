# SCSS Build System for zoid6/sites/www
# Compiles SCSS components to CSS with proper load paths
# Note: Pattern rules (.scss.css) compile all .scss files including partials (_vars.scss)
# Use explicit targets instead to avoid compiling partial files

# Main SCSS files to compile (not partial files starting with _)
MAIN_SCSS_FILES := finn.scss index.scss mantra.scss moneyday.scss ribbon.scss sitenews.scss www.scss

all:

# Explicit targets for main SCSS files (prevents compiling _vars.scss as standalone)
$(patsubst %.scss,%.css,$(MAIN_SCSS_FILES)):
	sass 	--load-path /home/opencode/data/work/zoid6/shared/skin/scss/ \
	     --load-path $(CURDIR) \
	     --sourcemap=none --stop-on-error --trace --style expanded $< $@

%.css: %.scss
	sass 	--load-path /home/opencode/data/work/zoid6/shared/skin/scss/ \
	     --load-path $(CURDIR) \
	     --sourcemap=none --stop-on-error --trace --style expanded $< $@

stage:
	sass 	--load-path /home/opencode/data/work/zoid6/shared/skin/scss/ \
	     --load-path $(CURDIR) \
	     --sourcemap=none --stop-on-error --trace --style expanded \
	     --update .:/srv/www/vhosts/zoidtechnologies.com/html/skin/css/

clean:
	-rm *~ *.css *.css.map .sass-cache

.SUFFIXES: .scss .css

.PHONY: clean stage
