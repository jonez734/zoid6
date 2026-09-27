all:

clean:
	-rm *~
	$(MAKE) -C tmpl clean
	$(MAKE) -C scss clean
	$(MAKE) -C fonts clean
	-$(MAKE) -C art clean
	$(MAKE) -C js clean

stage:
	$(MAKE) -C tmpl stage
	$(MAKE) -C scss stage
	$(MAKE) -C fonts stage
	-$(MAKE) -C art stage
	$(MAKE) -C js stage

.PHONY: all clean stage
