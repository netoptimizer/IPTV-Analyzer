#
# Standalone makefile for iptables MPEG2 TS kernel module and userspace
# shared library.
#
# Author: Jesper Dangaard Brouer <jdb@comx.dk>
#

# Targets
# -------
LIB_TARGETS := libxt_mp2t.so
obj-m += xt_mp2t.o
#obj-m += seq_example.o

# Environment variables that can be overwritten/redefined
# --------
KERNEL_DIR   ?= /lib/modules/$(shell uname -r)/build
#IPTABLES_INC ?= /usr/include
IPTABLES_INC ?= /usr/local/include
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

# We prefer to use 64bit kernels
ARCH ?= x86_64
export ARCH

# Trick to modify kernels CFLAGS
#KBUILD_CFLAGS+=-g -O0
#KBUILD_CFLAGS+=-g -O3
KBUILD_CFLAGS+=-g -O2 -fno-inline

PWD = $(shell pwd)

# FIXME: Don't assume that iptables are located in /usr/local/sbin

iptables_version = $(shell (/usr/local/sbin/iptables -V | awk {'print $$2'} | cut -c 2-))
IPT_FLAGS = -DIPTABLES_VERSION=\"$(iptables_version)\"

# TODO: Add a version check, that tell/warn people if the iptables
#       version is below 1.4.3 (as we depend >= 1.4.3)


all: lib modules

modules:
	@echo -e "\n  -=-=-=-=- Kernel modules -=-=-=-=-"
	$(MAKE) -C $(KERNEL_DIR) M=$(PWD) modules


sparse:
	@echo -e "\n  -=-=-=-=- SPARSE CHECK: Kernel modules -=-=-=-=-"
	$(MAKE) -C $(KERNEL_DIR) M=$(PWD) C=1 modules


clean:
#	$(MAKE) -C $(KERNEL_DIR) M=$(PWD) clean
	rm -rf .tmp_versions
	rm -f *.o *~ *.so *.ko
	rm -f *.mod.c Module.symvers modules.order
	rm -f .*.cmd
	rm -f .xt_mp2t.o.d


lib:	$(LIB_TARGETS)

lib%.so: lib%.o
	gcc -shared -o $@ $^;

lib%.o: lib%.c %.h
	@echo -e "\n  -=-=-=-=- Userspace lib -=-=-=-=-"
	gcc $(IPT_FLAGS) ${LIB_CFLAGS} -I$(IPTABLES_INC) -D_INIT=lib$*_init -c -o $@ $<;


# Old approach
lib_manual:	libxt_mp2t.c xt_mp2t.h
	gcc $(IPT_FLAGS) libxt_mp2t.c -D_INIT=libxt_mp2t_init \
		-fPIC -I$(IPTABLES_INC) -c -o libxt_mp2t.o
	gcc -shared -o libxt_mp2t.so libxt_mp2t.o
