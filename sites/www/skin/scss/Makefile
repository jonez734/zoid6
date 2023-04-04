scssfiles := $(wildcard *.scss)
cssfiles := $(scssfiles:.scss=.css)

#SCSS = sass --line-numbers --stop-on-error --trace --style expanded --sourcemap=none --load-path ../../../../common/

all:

.scss.css:
	$(SCSS) $< $@

install:
#	echo $(SCSSLOADPATH)
	$(SCSS) --update --watch .:$(DOCROOT)skin/css/ # also try 'compressed'
	#-$(installfile) *.css $(STAGESTATICDOCROOT)skin/css/


clean:
	-rm *~ *.css *.css.map

.SUFFIXES: .scss .css
