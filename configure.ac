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

# Check iptables binary exist and set variable IPTABLES to the program
# with full path.  Add "sbin" dirs to the search PATH as the user
# running configure might not have this in their path.
#
SBIN_PATH=$PATH:/usr/local/sbin:/usr/sbin:/sbin:
AC_PATH_PROG([IPTABLES], [iptables], [no], $SBIN_PATH)
if [[ "$IPTABLES" = "no" ]]; then
   AC_MSG_ERROR([Cannot find iptables binary - You need to install iptables])
fi
# Extract iptables base dirname / path
IPTABLES_BINDIR=`AS_DIRNAME([$IPTABLES])`
IPTABLES_DIR=`AS_DIRNAME([$IPTABLES_BINDIR])`

# xtlibdir the Xtables extensions path
AC_ARG_WITH([xtlibdir],
	AS_HELP_STRING([--with-xtlibdir=PATH],
	[Path where to install Xtables extensions.
	 Configure will try to auto DETECT, based on iptables binary path]),
	[xtlibdir="$withval"],
	[xtlibdir="detect"])
#	[xtlibdir='${libexecdir}/xtables'])

# Default setting of xtlibdir is "detect".
# ----------------------------------------
# Try to detect xtlibdir based upon the location of the detected
# iptables binary IPTABLES_DIR, unless --with-xtlibdir is used.
#
xtlibdir_check="";
if test "${with_xtlibdir+set}" = set; then
   # No detection and expantion needed, if specified via --with-xtlibdir
   xtlibdir_check=$xtlibdir;
elif test "${xtlibdir}" = "detect"; then

   AC_MSG_NOTICE([Entering auto-detect mode for Xtables extensions dir xtlibdir])
   test_dir=""
   if [[ "${IPTABLES_DIR}" = "/" ]]; then
      # Need to test for /lib{,64}/xtables/
      test_dir=/lib/xtables
      if [[ -d "$test_dir" ]]; then
          AC_MSG_NOTICE([ - Found xtlibdir: $test_dir])
	  xtlibdir=$test_dir
      else
	 test_dir=/lib64/xtables
         if [[ -d "$test_dir" ]]; then
             AC_MSG_NOTICE([ - Found xtlibdir: $test_dir])
	     xtlibdir=$test_dir
	 else
             xtlibdir=""
	     test_dir=""
	 fi
      fi
   else
      test_dir=${IPTABLES_DIR}/libexec/xtables
      if [[ -d "$test_dir" ]]; then
         AC_MSG_NOTICE([ - Found xtlibdir: $test_dir])
	 xtlibdir=$test_dir

         # Handle the case where IPTABLES_DIR = $prefix and
         # then pass it along with a quoted '$prefix'
         myprefix=${prefix}
         if [[ "${myprefix}" = "NONE" ]]; then
            myprefix=$ac_default_prefix
         fi
         if [[ "${myprefix}" = "${IPTABLES_DIR}" ]]; then
            xtlibdir='${prefix}/libexec/xtables'
         fi
         # Handle if exec_prefix is set
         if [[ "${exec_prefix}" != "NONE" ]]; then
            if [[ "${exec_prefix}" = "${IPTABLES_DIR}" ]]; then
               xtlibdir='${libexecdir}/xtables'
            fi
         fi
      fi
   fi
   # Double check the $test_dir
   xtlibdir_check=$test_dir
else
   # Skip/avoid check if "detect" is not in use/specified
   xtlibdir_check="/"
fi
#
# Test directory exist
if [[ ! -d "$xtlibdir_check" ]]; then
   AC_MSG_ERROR([Xtables extensions dir ${xtlibdir_check} does not exist], [2])
fi

PKG_CHECK_MODULES([libxtables], [xtables >= 1.4.3])
AC_CHECK_HEADERS([linux/netfilter/x_tables.h], [],
	[AC_MSG_ERROR([You need to have linux/netfilter/x_tables.h, see INSTALL file for details])])

regular_CFLAGS="-D_LARGEFILE_SOURCE=1 -D_LARGE_FILES -D_FILE_OFFSET_BITS=64 \
	-D_REENTRANT -Wall -Waggregate-return -Wmissing-declarations \
	-Wmissing-prototypes -Wredundant-decls -Wshadow -Wstrict-prototypes \
	-Winline -pipe -DXTABLES_LIBDIR=\\\"\${xtlibdir}\\\" \
	";

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
AC_SUBST([IPTABLES])
AC_SUBST([IPTABLES_DIR])
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
