#
# Notice: The VERSION is defined/controlled by AC_INIT
#
AC_INIT([iptv-analyzer], [0.7.1], [hawk@comx.dk], [], [www.iptv-analyzer.org])
#AC_INIT(package,        version, [bug-report], [tarname], [url])
#
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

# I would have liked to detect and check the $xtlibdir directory at
# this point, but its not possible as the variable $prefix and
# $exec_prefix variables are not set yet.  They get set after
# AC_OUTPUT, but after that point, its no longer possible to redefine
# $xtlibdir.
#
# Disable the check by testing if "/" exist
xtlibdir_check="/";
#
# In case, configure is called --with-xtlibdir, then it should be
# possible to check the directory.
#
if test "${with_xtlibdir+set}" = set; then
   # No expantion needed, if specified via --with-xtlibdir
   xtlibdir_check=$xtlibdir;
##else
##   # Need to expand the quoted '${libexecdir}/xtables'
##   myprefix=${prefix};
##   if [[ "${exec_prefix}" != "NONE" ]]; then
##      myprefix=`eval echo ${exec_prefix}`;
##   fi
##   xtlibdir_check=${myprefix}/libexec/xtables;
fi
#
# Test directories exist
if [[ ! -d "$xtlibdir_check" ]]; then
   AC_MSG_WARN([Xtables extensions dir ${xtlibdir_check} does not exist])
   AC_MSG_NOTICE([Entering auto-detect mode for Xtables extensions dir xtlibdir])
   if [[ -d /usr/local/libexec/xtables ]]; then
      AC_MSG_NOTICE([ - Found xtlibdir: /usr/local/libexec/xtables])
      xtlibdir=/usr/local/libexec/xtables

   elif [[ -d /lib/xtables ]]; then
      AC_MSG_NOTICE([ - Found xtlibdir: /lib/xtables])
      xtlibdir=/lib/xtables

   elif [[ -d /lib64/xtables ]]; then
      AC_MSG_NOTICE([ - Found xtlibdir: /lib64/xtables])
      xtlibdir=/lib64/xtables

   else
      AC_MSG_ERROR([ - Cannot find a xtlibdir], [2])
   fi
fi



PKG_CHECK_MODULES([libxtables], [xtables >= 1.4.3])
AC_CHECK_HEADERS([linux/netfilter/x_tables.h], [],
	[AC_MSG_ERROR([You need to have linux/netfilter/x_tables.h, see INSTALL file for details])])

regular_CFLAGS="-D_LARGEFILE_SOURCE=1 -D_LARGE_FILES -D_FILE_OFFSET_BITS=64 \
	-D_REENTRANT -Wall -Waggregate-return -Wmissing-declarations \
	-Wmissing-prototypes -Wredundant-decls -Wshadow -Wstrict-prototypes \
	-Winline -pipe -DXTABLES_LIBDIR=\\\"\${xtlibdir}\\\" \
	-I\${XA_TOPSRCDIR}/include";

#
# Check kernel version
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
if test "$kmajor" -gt 2 -o "$kminor" -gt 6 -o "$kmicro" -gt 38; then
	echo "WARNING: You are trying a newer kernel. Results may vary. :-)";
elif test \( "$kmajor" -lt 2 -o "$kminor" -lt 6 -o "$kmicro" -lt 28 \); then
	echo "ERROR: Your kernel version is not supported";
	echo "       Minimum kernel versions > 2.6.27";
	echo "       (as we need the RCU locking API).";
	exit 1;
fi;

AC_SUBST([regular_CFLAGS])
AC_SUBST([kbuilddir])
AC_SUBST([xtlibdir])
AC_CONFIG_FILES([
	Makefile
	iptables-module/Makefile
])

AC_OUTPUT

echo "-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=--=-=-=-=-=-=-=-=-"
echo "   Visit the project homepage at:"
echo "       http://$PACKAGE_URL"
echo ""
echo "   Send bugreports, patches and insults to:"
echo "       $PACKAGE_BUGREPORT"
echo "-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=--=-=-=-=-=-=-=-=-"
