/*
 * Userspace interface for MPEG2 TS match extension "mpeg2ts" for Xtables.
 *
 * Copyright (c) Jesper Dangaard Brouer <jdb@comx.dk>, 2009+
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License; either
 * version 2 of the License, or any later version, as published by the
 * Free Software Foundation.
 *
 */

#include <getopt.h>
#include <netdb.h>
#include <stdbool.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <stddef.h>

#include <xtables.h>
#include "xt_mpeg2ts.h"

/*
 * Userspace iptables/xtables interface for mpeg2ts module.
 */

/* FIXME: don't think this compat check does not cover all versions */
#ifndef XTABLES_VERSION
#define xtables_error exit_error
#endif

static const struct option mp2t_mt_opts[] = {
	{.name = "name",	.has_arg = true,  .val = 'n'},
	{.name = "drop",	.has_arg = false, .val = 'd'},
	{.name = "drop-detect",	.has_arg = false, .val = 'd'},
	{.name = "max",		.has_arg = true,  .val = 'x'},
	{.name = "max-streams",	.has_arg = true,  .val = 'x'},
	{NULL},
};

static void mp2t_mt_help(void)
{
	printf(
"mpeg2ts (MPEG2 Transport Stream) match options:\n"
"VERSION %s\n"
"   [--name <name>]        Name for proc file /proc/net/xt_mpeg2ts/rule_NAME\n"
"   [--drop-detect]        Match lost TS frames (occured before this packet)\n"
"   [--max-streams <num>]  Track 'max' number of streams (per rule)\n",
		version
		);
}

static void mp2t_mt_init(struct xt_entry_match *match)
{
	struct xt_mp2t_mtinfo *info = (void *)match->data;
	/* Enable drop detection per default */
	info->flags = XT_MP2T_DETECT_DROP;
}

static int mp2t_mt_parse(int c, char **argv, int invert, unsigned int *flags,
			 const void *entry, struct xt_entry_match **match)
{
	struct xt_mp2t_mtinfo *info = (void *)(*match)->data;
	uint32_t num;

	switch (c) {
	case 'n': /* --name */
		xtables_param_act(XTF_ONLY_ONCE, "mpeg2ts", "--name",
				  *flags & XT_MP2T_PARAM_NAME);
		if (invert)
			xtables_error(PARAMETER_PROBLEM, "Inverting name?");
		if (strlen(optarg) == 0)
			xtables_error(PARAMETER_PROBLEM, "Zero-length name?");
		if (strchr(optarg, '"') != NULL)
			xtables_error(PARAMETER_PROBLEM,
				      "Illegal character in name (\")!");
		strncpy(info->rule_name, optarg, sizeof(info->rule_name));
		info->flags |= XT_MP2T_PARAM_NAME;
		*flags |= XT_MP2T_PARAM_NAME;
		break;

	case 'd': /* --drop-detect */
		if (*flags & XT_MP2T_DETECT_DROP)
			xtables_error(PARAMETER_PROBLEM,
				      "Can't specify --drop option twice");
		*flags |= XT_MP2T_DETECT_DROP;

		if (invert)
			info->flags &= ~XT_MP2T_DETECT_DROP;
		else
			info->flags |= XT_MP2T_DETECT_DROP;

		break;

	case 'x': /* --max-streams */
		if (*flags & XT_MP2T_MAX_STREAMS)
			xtables_error(PARAMETER_PROBLEM,
				"Can't specify --max-streams option twice");
		*flags |= XT_MP2T_MAX_STREAMS;

		if (invert) {
			info->cfg.max = 0;
			/* printf("inverted\n"); */
			break;
		}

		/* OLD iptables style
		if (string_to_number(optarg, 0, 0xffffffff, &num) == -1)
			xtables_error(PARAMETER_PROBLEM,
				      "bad --max-stream: `%s'", optarg);
		*/

		/* C-style
		char *end;
		num = strtoul(optarg, &end, 0);
		*/

		/* New xtables style */
		if (!xtables_strtoui(optarg, NULL, &num, 0, UINT32_MAX))
			xtables_error(PARAMETER_PROBLEM,
				      "bad --max-stream: `%s'", optarg);

		/* DEBUG: printf("--max-stream=%lu\n", num); */
		info->flags |= XT_MP2T_MAX_STREAMS;
		info->cfg.max = num;

		break;

	default:
		return false;
	}

	return true;
}

static void mp2t_mt_print(const void *entry,
			  const struct xt_entry_match *match, int numeric)
{
	const struct xt_mp2t_mtinfo *info = (const void *)(match->data);

	/* Always indicate this is a mpeg2ts match rule */
	printf("mpeg2ts match");

	if (info->flags & XT_MP2T_PARAM_NAME)
		printf(" name:\"%s\"", info->rule_name);

	if (!(info->flags & XT_MP2T_DETECT_DROP))
		printf(" !drop-detect");

	if (info->flags & XT_MP2T_MAX_STREAMS)
		printf(" max-streams:%u ", info->cfg.max);
}

static void mp2t_mt_save(const void *entry,
			 const struct xt_entry_match *match)
{
	const struct xt_mp2t_mtinfo *info = (const void *)(match->data);

	/* We need to handle --name, --drop-detect, and --max-streams. */
	if (info->flags & XT_MP2T_PARAM_NAME)
		printf("--name \"%s\" ",  info->rule_name);

	if (!(info->flags & XT_MP2T_DETECT_DROP))
		printf("! --drop-detect ");

	if (info->flags & XT_MP2T_MAX_STREAMS)
		printf("--max-streams %u ", info->cfg.max);

}

static struct xtables_match mp2t_mt_reg = {
	.version        = XTABLES_VERSION,
	.name           = "mpeg2ts",
	.revision       = 0,
	.family         = PF_UNSPEC,
	.size           = XT_ALIGN(sizeof(struct xt_mp2t_mtinfo)),
	.userspacesize  = offsetof(struct xt_mp2t_mtinfo, hinfo),
	.init           = mp2t_mt_init,
	.help           = mp2t_mt_help,
	.parse          = mp2t_mt_parse,
/*	.final_check    = mp2t_mt_check,*/
	.print          = mp2t_mt_print,
	.save           = mp2t_mt_save,
	.extra_opts     = mp2t_mt_opts,
};

static void _init(void)
{
	xtables_register_match(&mp2t_mt_reg);
}
