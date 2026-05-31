# Makefile for projects.zoidtechnologies.com

PROD := merlin:/srv/www/vhosts/projects.zoidtechnologies.com/
STAGE := /srv/www/vhosts/projects.zoidtechnologies.com/

.PHONY: all deploy stage clean help

all: stage

stage:
	@echo "Staging to $(STAGE)..."
	@mkdir -p $(STAGE)/html

deploy: stage
	@echo "Deploying to $(PROD)..."
	rsync -av --delete $(STAGE)/ $(PROD)

clean:
	rm -rf /tmp/projects-stage

help:
	@echo "projects - projects.zoidtechnologies.com deployment"
	@echo ""
	@echo "Targets:"
	@echo "  stage   - Stage files locally"
	@echo "  deploy  - Deploy to production"
	@echo "  clean   - Clean staged files"