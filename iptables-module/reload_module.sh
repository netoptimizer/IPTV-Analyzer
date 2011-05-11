#!/bin/bash
#
# Quick script for reloading the kernel module, from the current
# directory.

MODULE=xt_mpeg2ts

echo "Reloading kernel module:"

# Test if a module is loaded
module_loaded() {
    module=$1
    echo " -- Test if module:$module is loaded"
    egrep -q ^${module} /proc/modules 2> /dev/null
    res=$?
    return $res
}

module_rmmod() {
    module=$1
    echo " -- Unloading module: $module"
    sudo rmmod $module
    res=$?
    return $res
}    

module_insmod() {
    module=$1
    lmodule=${module}.ko
    echo " -- Loading module: ${lmodule}"
    if [ -e ${lmodule} ]; then
	sudo insmod ./${lmodule}
    else
	echo "ERROR: cannot find module file: ${lmodule}"
    fi
    res=$?
    return $res
}    

iptables_flush() {
    echo " -- Flushing iptables rules"
    sudo iptables -F
}

iptables_create_rules() {
    echo " -- Creating iptables rules using mpeg2ts"
    sudo iptables -I INPUT -p udp -m mpeg2ts --name input
}

echo " -- Check if I need to load compat_xtables"
compat=compat_xtables
if (module_loaded $compat); then
    echo " ---- OK, compat_xtables already loaded"
else
    echo " ---- Loading compat_xtables"
    sudo insmod ./${compat}.ko
fi

if (module_rmmod $MODULE); then
    echo " ---- Successfully removed module"
    if (! module_insmod $MODULE); then
	echo "ERROR(2): Cannot load module, FAILING!"
	exit 2
    fi
else
    echo " ---- Cannot remove module"
    echo " ---- Assuming iptables rules have references"
    iptables_flush
    # Try again
    if (module_rmmod $MODULE); then
	echo " ---- Successfully removed module"
	if (! module_insmod $MODULE); then
	    echo "ERROR(3): Cannot load module, FAILING!"
	    exit 3
	fi
    else
	echo "ERROR(4): Still cannot remove module, FAILING!"
	exit 4
    fi
fi

iptables_create_rules
