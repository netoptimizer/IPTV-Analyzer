#ifndef _COMPAT_NFINETADDR_H
#define _COMPAT_NFINETADDR_H 1

#include <linux/in.h>
#include <linux/in6.h>

union nf_inet_addr {
	__be32 ip;
	__be32 ip6[4];
	struct in_addr in;
	struct in6_addr in6;
};

#endif /* _COMPAT_NFINETADDR_H */
