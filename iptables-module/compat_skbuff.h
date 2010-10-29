#ifndef COMPAT_SKBUFF_H
#define COMPAT_SKBUFF_H 1

struct tcphdr;
struct udphdr;

#if LINUX_VERSION_CODE <= KERNEL_VERSION(2, 6, 30)
static inline void skb_dst_set(struct sk_buff *skb, struct dst_entry *dst)
{
	skb->dst = dst;
}

static inline struct dst_entry *skb_dst(const struct sk_buff *skb)
{
	return skb->dst;
}

static inline struct rtable *skb_rtable(const struct sk_buff *skb)
{
	return (void *)skb->dst;
}
#endif

#if LINUX_VERSION_CODE <= KERNEL_VERSION(2, 6, 19)
#	define skb_ifindex(skb) \
		(((skb)->input_dev != NULL) ? (skb)->input_dev->ifindex : 0)
#	define skb_nfmark(skb) (((struct sk_buff *)(skb))->nfmark)
#elif LINUX_VERSION_CODE <= KERNEL_VERSION(2, 6, 32)
#	define skb_ifindex(skb) (skb)->iif
#	define skb_nfmark(skb) (((struct sk_buff *)(skb))->mark)
#else
#	define skb_ifindex(skb) (skb)->skb_iif
#	define skb_nfmark(skb) (((struct sk_buff *)(skb))->mark)
#endif

#ifdef CONFIG_NETWORK_SECMARK
#	define skb_secmark(skb) ((skb)->secmark)
#else
#	define skb_secmark(skb) 0
#endif

#if LINUX_VERSION_CODE <= KERNEL_VERSION(2, 6, 21)
#	define ip_hdr(skb) ((skb)->nh.iph)
#	define ip_hdrlen(skb) (ip_hdr(skb)->ihl * 4)
#	define ipv6_hdr(skb) ((skb)->nh.ipv6h)
#	define skb_network_header(skb) ((skb)->nh.raw)
#	define skb_transport_header(skb) ((skb)->h.raw)
static inline void skb_reset_network_header(struct sk_buff *skb)
{
	skb->nh.raw = skb->data;
}
static inline struct tcphdr *tcp_hdr(const struct sk_buff *skb)
{
	return (void *)skb_transport_header(skb);
}
static inline struct udphdr *udp_hdr(const struct sk_buff *skb)
{
	return (void *)skb_transport_header(skb);
}
#endif

#endif /* COMPAT_SKBUFF_H */
