#
# Standalone makefile for iptables MPEG2 TS kernel module and userspace
# shared library.
#
# Author: Jesper Dangaard Brouer <jdb@comx.dk>
#

# Targets
# -------
LIB_TARGETS := libxt_mpeg2ts.so
obj-m += xt_mpeg2ts.o
#obj-m += seq_example.o

# Jan Engelhardt's compat_xtables module stolen from xtables-addon project
#  git://xtables-addons.git.sf.net/gitroot/xtables-addons/xtables-addons/
obj-m += compat_xtables.o


# Environment variables that can be overwritten/redefined
# --------
KERNEL_DIR   ?= /lib/modules/$(shell uname -r)/build
LIB_CFLAGS   ?=-O2 -ggdb3 -Wall -fPIC

# Trick I found in kernel/Documentation/kbuild/modules.txt:
#
# Use make variable KBUILD_EXTRA_SYMBOLS in the Makefile
#   If it is impractical to copy Module.symvers from another
#   module, you can assign a space separated list of files to
#   KBUILD_EXTRA_SYMBOLS in your Makfile. These files will be
#   loaded by modpost during the initialisation of its symbol
#   tables.
#

# Its possible to compile the module against a 64-bit kernel,
# on a 32-bit system, by define ARCH=x86_64
#
# You can simply call:
#   make ARCH=x86_64
#
# ARCH ?= x86_64
# export ARCH

# Trick to modify kernels CFLAGS
#KBUILD_CFLAGS+=-g -O0
#KBUILD_CFLAGS+=-g -O3
KBUILD_CFLAGS+=-g -O2 -fno-inline

PWD = $(shell pwd)
CP = cp -v

# Need sbin in the path to find iptables binary
PATH:=/usr/local/sbin:/usr/sbin:/sbin:$(PATH)

# Auto detect location of iptables, don't assume /sbin
iptables_bin = $(shell which iptables)
iptables_version = $(shell ($(iptables_bin) -V | awk {'print $$2'} | cut -c 2-))
IPT_FLAGS = -DIPTABLES_VERSION=\"$(iptables_version)\"

# Problem statement:
#
#  The userspace lib dir is differs on different Linux distributions,
#  further more the distributions choose not to follow the source code
#  (and just alter the exec_prefix to libexec).  Instead they force
#  the usage of /lib/xtables/ or /lib64/xtables/, via
#
#    ./configure --with-xtlibdir=/lib/xtables
#
# Auto detect userspace lib dir and prefix
iptables_bindir = $(shell dirname $(iptables_bin))
iptables_prefix = $(shell dirname $(iptables_bindir))
IPTABLES_INC   ?= /usr/include
#IPTABLES_INC ?= /usr/local/include
#xtlibdir ?= /lib/xtables
xtlibdir ?= $(shell if [ "$(iptables_bindir)" = "/sbin" ]; then echo "/lib/xtables"; else echo "$(iptables_prefix)/libexec/xtables"; fi)

# TODO: Add a version check, that tell/warn people if the iptables
#       version is below 1.4.3 (as we depend >= 1.4.3)

all: lib modules

#testxtlibdir:
#	echo xtlibdir: $(xtlibdir)
#	echo $(PATH)

modules:
	@echo -e "\n  -=-=-=-=- Kernel modules -=-=-=-=-"
	$(MAKE) -C $(KERNEL_DIR) M=$(PWD) modules

modules_install: modules
	@echo -e "\n  -=-=-=-=- INSTALL Kernel modules -=-=-=-=-"
	$(MAKE) -C $(KERNEL_DIR) M=$(PWD) modules_install

sparse:
	@echo -e "\n  -=-=-=-=- SPARSE CHECK: Kernel modules -=-=-=-=-"
	$(MAKE) -C $(KERNEL_DIR) M=$(PWD) C=1 modules

# Install target for userspace lib
lib_install: lib
	@echo "\n  -=-=-=-=- INSTALL Userspace lib -=-=-=-=-"
	@if test -d $(xtlibdir); then \
	    $(CP) $(LIB_TARGETS) $(xtlibdir); \
	else \
	    echo "***ERROR*** - cannot find libexec install dir"; \
	fi

install: lib_install modules_install


clean:
#	$(MAKE) -C $(KERNEL_DIR) M=$(PWD) clean
	rm -rf .tmp_versions
	rm -f *.o *~ *.so *.ko
	rm -f *.mod.c Module.symvers modules.order
	rm -f .*.cmd
	rm -f .xt_mpeg2ts.o.d


lib:	$(LIB_TARGETS)

lib%.so: lib%.o
	gcc -shared -o $@ $^;

lib%.o: lib%.c %.h
	@echo -e "\n  -=-=-=-=- Userspace lib -=-=-=-=-"
	gcc $(IPT_FLAGS) ${LIB_CFLAGS} -I$(IPTABLES_INC) -D_INIT=lib$*_init -c -o $@ $<;


# Old approach
lib_manual:	libxt_mpeg2ts.c xt_mpeg2ts.h
	gcc $(IPT_FLAGS) libxt_mpeg2ts.c -D_INIT=libxt_mpeg2ts_init \
		-fPIC -I$(IPTABLES_INC) -c -o libxt_mpeg2ts.o
	gcc -shared -o libxt_mpeg2ts.so libxt_mpeg2ts.o
