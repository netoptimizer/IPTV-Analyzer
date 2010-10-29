#ifndef _XTABLES_COMPAT_H
#define _XTABLES_COMPAT_H 1

#include <linux/kernel.h>
#include <linux/version.h>
#include "compat_skbuff.h"
#include "compat_xtnu.h"

#define DEBUGP Use__pr_debug__instead

#if LINUX_VERSION_CODE < KERNEL_VERSION(2, 6, 17)
#	warning Kernels below 2.6.17 not supported.
#endif

#if defined(CONFIG_NF_CONNTRACK) || defined(CONFIG_NF_CONNTRACK_MODULE)
#	if !defined(CONFIG_NF_CONNTRACK_MARK)
#		warning You have CONFIG_NF_CONNTRACK enabled, but CONFIG_NF_CONNTRACK_MARK is not (please enable).
#	endif
#	include <net/netfilter/nf_conntrack.h>
#elif defined(CONFIG_IP_NF_CONNTRACK) || defined(CONFIG_IP_NF_CONNTRACK_MODULE)
#	if !defined(CONFIG_IP_NF_CONNTRACK_MARK)
#		warning You have CONFIG_IP_NF_CONNTRACK enabled, but CONFIG_IP_NF_CONNTRACK_MARK is not (please enable).
#	endif
#	include <linux/netfilter_ipv4/ip_conntrack.h>
#	define nf_conn ip_conntrack
#	define nf_ct_get ip_conntrack_get
#	define nf_conntrack_untracked ip_conntrack_untracked
#else
#	warning You need either CONFIG_NF_CONNTRACK or CONFIG_IP_NF_CONNTRACK.
#endif

#if LINUX_VERSION_CODE <= KERNEL_VERSION(2, 6, 17)
#	define skb_init_secmark(skb)
#	define skb_linearize	xtnu_skb_linearize
#endif

#if LINUX_VERSION_CODE <= KERNEL_VERSION(2, 6, 19)
#	define neigh_hh_output xtnu_neigh_hh_output
#	define IPPROTO_UDPLITE 136
#	define CSUM_MANGLED_0 ((__force __sum16)0xffff)
#endif

#if LINUX_VERSION_CODE <= KERNEL_VERSION(2, 6, 24)
#	define NF_INET_PRE_ROUTING  NF_IP_PRE_ROUTING
#	define NF_INET_LOCAL_IN     NF_IP_LOCAL_IN
#	define NF_INET_FORWARD      NF_IP_FORWARD
#	define NF_INET_LOCAL_OUT    NF_IP_LOCAL_OUT
#	define NF_INET_POST_ROUTING NF_IP_POST_ROUTING
#	define ip_local_out         xtnu_ip_local_out
#	define ip_route_output_key  xtnu_ip_route_output_key
#	include "compat_nfinetaddr.h"
#endif

#if LINUX_VERSION_CODE <= KERNEL_VERSION(2, 6, 23)
#	define init_net               xtnu_ip_route_output_key /* yes */
#	define init_net__loopback_dev (&loopback_dev)
#	define init_net__proc_net     proc_net
#else
#	define init_net__loopback_dev init_net.loopback_dev
#	define init_net__proc_net     init_net.proc_net
#endif

#if LINUX_VERSION_CODE <= KERNEL_VERSION(2, 6, 34)
#	define xt_match              xtnu_match
#	define xt_register_match     xtnu_register_match
#	define xt_unregister_match   xtnu_unregister_match
#	define xt_register_matches   xtnu_register_matches
#	define xt_unregister_matches xtnu_unregister_matches
#endif

#if LINUX_VERSION_CODE <= KERNEL_VERSION(2, 6, 19)
#	define csum_replace2 xtnu_csum_replace2
#	define csum_replace4 xtnu_csum_replace4
#	define inet_proto_csum_replace4 xtnu_proto_csum_replace4
#elif LINUX_VERSION_CODE <= KERNEL_VERSION(2, 6, 24)
#	define csum_replace2 nf_csum_replace2
#	define csum_replace4 nf_csum_replace4
#	define inet_proto_csum_replace4 xtnu_proto_csum_replace4
#endif

#if LINUX_VERSION_CODE >= KERNEL_VERSION(2, 6, 34)
#	define ipt_unregister_table(tbl) ipt_unregister_table(&init_net, (tbl))
#	define ip6t_unregister_table(tbl) ip6t_unregister_table(&init_net, (tbl))
#else
#	define ipt_unregister_table(tbl) ipt_unregister_table(tbl)
#	define ip6t_unregister_table(tbl) ip6t_unregister_table(tbl)
#endif


#if !defined(NIP6) && !defined(NIP6_FMT)
#	define NIP6(addr) \
		ntohs((addr).s6_addr16[0]), \
		ntohs((addr).s6_addr16[1]), \
		ntohs((addr).s6_addr16[2]), \
		ntohs((addr).s6_addr16[3]), \
		ntohs((addr).s6_addr16[4]), \
		ntohs((addr).s6_addr16[5]), \
		ntohs((addr).s6_addr16[6]), \
		ntohs((addr).s6_addr16[7])
#	define NIP6_FMT "%04x:%04x:%04x:%04x:%04x:%04x:%04x:%04x"
#endif
#if !defined(NIPQUAD) && !defined(NIPQUAD_FMT)
#	define NIPQUAD(addr) \
		((const unsigned char *)&addr)[0], \
		((const unsigned char *)&addr)[1], \
		((const unsigned char *)&addr)[2], \
		((const unsigned char *)&addr)[3]
#	define NIPQUAD_FMT "%u.%u.%u.%u"
#endif

#define ip_route_me_harder    xtnu_ip_route_me_harder
#define skb_make_writable     xtnu_skb_make_writable
#define xt_target             xtnu_target
#define xt_register_target    xtnu_register_target
#define xt_unregister_target  xtnu_unregister_target
#define xt_register_targets   xtnu_register_targets
#define xt_unregister_targets xtnu_unregister_targets

#define xt_request_find_match xtnu_request_find_match

#endif /* _XTABLES_COMPAT_H */
