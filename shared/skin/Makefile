all:

# There is no shared/skin/art/ directory; the art step was removed.
clean:
	-rm *~
	$(MAKE) -C tmpl clean
	$(MAKE) -C scss clean
	$(MAKE) -C fonts clean
	$(MAKE) -C js clean

stage:
	$(MAKE) -C tmpl stage
	$(MAKE) -C scss stage
	$(MAKE) -C fonts stage
	$(MAKE) -C js stage

.PHONY: all clean stage
