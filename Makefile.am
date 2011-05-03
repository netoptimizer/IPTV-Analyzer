# -*- Makefile -*-

ACLOCAL_AMFLAGS  = -I m4
#SUBDIRS          = extensions geoip

#man_MANS := xtables-addons.8

.PHONY: FORCE
FORCE:

#xtables-addons.8: FORCE
#	${MAKE} -f Makefile.mans all;

#install-exec-hook:
#	depmod -a || :;

#config.status: Makefile.iptrules.in

tmpdir := $(shell mktemp -dtu)
packer  = gzip
packext = .tar.gz

.PHONY: tarball
tarball:
# do not use mkdir_p here.
	mkdir ${tmpdir}
	pushd ${top_srcdir} && git archive --prefix=${PACKAGE_NAME}-${PACKAGE_VERSION}/ HEAD | tar -C ${tmpdir} -x && popd;
	pushd ${tmpdir}/${PACKAGE_NAME}-${PACKAGE_VERSION} && ./autogen.sh && popd;
	tar --use=${packer} -C ${tmpdir} -cf ${PACKAGE_NAME}-${PACKAGE_VERSION}${packext} --owner=root --group=root ${PACKAGE_NAME}-${PACKAGE_VERSION}/;
	rm -Rf ${tmpdir};
