#
# Notice: The VERSION is defined/controlled by AC_INIT
#
#AC_INIT([iptv-analyzer], [0.7.1])
AC_INIT([iptv-analyzer], [0.7.1], [hawk@comx.dk], [], [www.iptv-analyzer.org])
AC_CONFIG_HEADERS([config.h])
AC_CONFIG_MACRO_DIR([m4])
#AC_PROG_INSTALL
#AM_INIT_AUTOMAKE([1.10.2 -Wall foreign subdir-objects])
AM_INIT_AUTOMAKE([foreign])
AC_PROG_CC
AM_PROG_CC_C_O
#AC_DISABLE_STATIC
#AC_PROG_LIBTOOL

AC_ARG_WITH([kbuild],
	AS_HELP_STRING([--with-kbuild=PATH],
	[Path to kernel build directory [[/lib/modules/CURRENT/build]]]),
	[kbuilddir="$withval"],
	[kbuilddir="/lib/modules/$(uname -r)/build"])
#
# check for --without-kbuild
#
if [[ "$kbuilddir" == no ]]; then
	kbuilddir="";
fi

AC_ARG_WITH([xtlibdir],
	AS_HELP_STRING([--with-xtlibdir=PATH],
	[Path where to install Xtables extensions [[LIBEXECDIR/xtables]]]),
	[xtlibdir="$withval"],
	[xtlibdir='${libexecdir}/xtables'])

PKG_CHECK_MODULES([libxtables], [xtables >= 1.4.3])
AC_CHECK_HEADERS([linux/netfilter/x_tables.h], [],
	[AC_MSG_ERROR([You need to have linux/netfilter/x_tables.h, see INSTALL file for details])])

regular_CFLAGS="-D_LARGEFILE_SOURCE=1 -D_LARGE_FILES -D_FILE_OFFSET_BITS=64 \
	-D_REENTRANT -Wall -Waggregate-return -Wmissing-declarations \
	-Wmissing-prototypes -Wredundant-decls -Wshadow -Wstrict-prototypes \
	-Winline -pipe -DXTABLES_LIBDIR=\\\"\${xtlibdir}\\\" \
	-I\${XA_TOPSRCDIR}/include";

#
# check kernel version
#
if grep -q "CentOS release 5\." /etc/redhat-release 2>/dev/null ||
    grep -q "Red Hat Enterprise Linux Server release 5" /etc/redhat-release 2>/dev/null; then
	#
	# Well, just a warning. Maybe the admin updated the kernel.
	echo "WARNING: This distribution's shipped kernel is not supported.";
fi;
krel="$(make -sC ${kbuilddir} kernelrelease)";
krel="${krel%%-*}";
kmajor="${krel%%.*}";
krel="${krel#*.}";
kminor="${krel%%.*}";
krel="${krel#*.}";
kmicro="${krel%%.*}";
if test "$kmicro" = "$krel"; then
	kstable=0;
else
	kstable="${krel#*.}";
	if test -z "$kstable"; then
		kstable=0;
	fi;
fi;
echo "Found kernel version $kmajor.$kminor.$kmicro.$kstable in $kbuilddir";
if test "$kmajor" -gt 2 -o "$kminor" -gt 6 -o "$kmicro" -gt 36; then
	echo "WARNING: You are trying a newer kernel. Results may vary. :-)";
elif test \( "$kmajor" -lt 2 -o "$kminor" -lt 6 -o "$kmicro" -lt 17 \) -o \
    \( "$kmajor" -eq 2 -a "$kminor" -eq 6 -a "$kmicro" -eq 18 -a \
    "$kstable" -lt 5 \); then
	echo "ERROR: That kernel version is not supported. Please see INSTALL for minimum configuration.";
	exit 1;
fi;

AC_SUBST([regular_CFLAGS])
AC_SUBST([kbuilddir])
AC_SUBST([xtlibdir])
AC_CONFIG_FILES([Makefile])
AC_OUTPUT
