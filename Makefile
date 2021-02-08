SHELL := /usr/bin/env bash
RPM_TMP=/build/rpmbuild
PKG_NAME=tuleap-plugin-botmattermost-git
VERSION=$(shell LANG=C cat VERSION)

ifeq ($(RELEASE),)
	RELEASE=1
endif
BASE_DIR=$(shell pwd)
RPMBUILD=rpmbuild --define "_topdir $(RPM_TMP)" --define "_tmppath /build" --define "dist $(DIST)"

NAME_VERSION=$(PKG_NAME)-$(VERSION)

all: rpm

rpm: $(RPM_TMP)/RPMS/noarch/$(NAME_VERSION)-$(RELEASE)$(DIST).noarch.rpm
	@echo "Results: $^"

$(RPM_TMP)/RPMS/noarch/%.noarch.rpm: $(RPM_TMP)/SRPMS/%.src.rpm
	$(RPMBUILD) --rebuild $<

$(RPM_TMP)/SRPMS/%-$(VERSION)-$(RELEASE)$(DIST).src.rpm: $(RPM_TMP)/SPECS/%.spec $(RPM_TMP)/SOURCES/%-$(VERSION).tar.gz
	$(RPMBUILD) -bs $(RPM_TMP)/SPECS/$*.spec

$(RPM_TMP)/SPECS/%.spec: $(BASE_DIR)/%.spec
	cat $< | \
		sed -e 's/@@VERSION@@/$(VERSION)/g' |\
		sed -e 's/@@RELEASE@@/$(RELEASE)/g' \
		> $@

.PHONY: build
build: export HOME = "/build"
build: export TMPDIR = "/build"
build:
	cd /build/src && CYPRESS_INSTALL_BINARY=0 npm install --no-audit && \
    cd /build/src/plugins/botmattermost_git && npm install && npm run build

$(RPM_TMP)/SOURCES/$(NAME_VERSION).tar.gz: build $(RPM_TMP)
	[ -h $(RPM_TMP)/SOURCES/$(NAME_VERSION) ] || ln -s $(BASE_DIR) $(RPM_TMP)/SOURCES/$(NAME_VERSION)
	[ ! -d $(RPM_TMP)/SOURCES/$(NAME_VERSION)/assets ] || rm -rf $(RPM_TMP)/SOURCES/$(NAME_VERSION)/assets
	cp -ar $(BASE_DIR)/../../src/www/assets/botmattermost_git $(RPM_TMP)/SOURCES/$(NAME_VERSION)/assets
	cd $(RPM_TMP)/SOURCES && \
		find $(NAME_VERSION)/ \(\
		-path $(NAME_VERSION)/tests -o\
		-name 'node_modules' -o\
		-name '*.spec' -o\
		-name 'Makefile' -o\
		-name ".git" -o\
		-name ".gitignore" -o\
		-name ".gitmodules" -o\
		-name "*~" -o\
		-path "*/.DS_Store"-o\
		-path "nbproject"-o\
		\)\
		-prune -o -print |\
		 cpio -o -H ustar --quiet |\
		 gzip > $(RPM_TMP)/SOURCES/$(NAME_VERSION).tar.gz

$(RPM_TMP):
	@[ -d $@ ] || mkdir -p $@ $@/BUILD $@/RPMS $@/SOURCES $@/SPECS $@/SRPMS $@/TMP

docker-run:
	pushd /tuleap && git checkout-index -a --prefix=/build/src/ && popd
	cp -Rf /plugin/ /build/src/plugins/botmattermost_git
	make -C /build/src/plugins/botmattermost_git all RELEASE=$(RELEASE) DIST=.$(DIST)
	install -m 0644 /build/rpmbuild/RPMS/noarch/*.rpm /output
