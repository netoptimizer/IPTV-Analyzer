/*
 * Header file for MPEG2 TS match extension "mp2t" for Xtables.
 *
 * Copyright (c) Jesper Dangaard Brouer <jdb@comx.dk>, 2009+
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License; either
 * version 2 of the License, or any later version, as published by the
 * Free Software Foundation.
 *
 */
#ifndef _LINUX_NETFILTER_XT_MP2T_MATCH_H
#define _LINUX_NETFILTER_XT_MP2T_MATCH_H 1

#define XT_MODULE_NAME		"xt_mp2t"
#define XT_MODULE_VERSION	"0.3.1"
#define XT_MODULE_RELDATE	"Nov 17, 2010"
#define PFX			XT_MODULE_NAME ": "

static char version[] =
	XT_MODULE_NAME ".c:v" XT_MODULE_VERSION " (" XT_MODULE_RELDATE ")";

enum {
	XT_MP2T_DETECT_DROP = 1 << 0,
	XT_MP2T_MAX_STREAMS = 1 << 1,
	XT_MP2T_PARAM_NAME  = 1 << 2,
};

/* Details of this hash structure is hidden in kernel space xt_mp2t.c */
struct xt_rule_mp2t_conn_htable;

struct mp2t_cfg {

	/* Hash table setup */
	u_int32_t size;		/* how many hash buckets */
	u_int32_t max;		/* max number of entries */
	u_int32_t max_list;	/* warn if list searches exceed this number */
};


struct xt_mp2t_mtinfo {
	__u16 flags;

	/* FIXME:

	   I need to fix the problem, where I have to reallocated data
	   each time a single rule change occur.

	   The idea with rule_name and rule_id is that the name is
	   optional, simply to provide a name in /proc/, the rule_id
	   is the real lookup-key in the internal kernel list of the
	   rules associated dynamic-allocated-data.

	 */
	char rule_name[IFNAMSIZ];

	struct mp2t_cfg cfg;

	/** Below used internally by the kernel **/
	__u32 rule_id;

	/* Hash table pointer */
	struct xt_rule_mp2t_conn_htable *hinfo __attribute__((aligned(8)));
};

#endif /* _LINUX_NETFILTER_XT_MP2T_MATCH_H */
