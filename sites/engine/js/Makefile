all:

clean:
	-rm *~

install:
	mkdir -p $(DOCROOT)js/
#	-$(RSYNC) --copy-dirlinks tinymce $(DOCROOT)/js/
	$(RSYNC) *.js $(DOCROOT)js/
