/*
 * Userspace interface for MPEG2 TS match extension "mpeg2ts" for Xtables.
 *
 * Copyright (c) Jesper Dangaard Brouer <netoptimizer@brouer.com>, 2009-2013
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License; either
 * version 2 of the License, or any later version, as published by the
 * Free Software Foundation.  See <http://www.gnu.org/licenses/gpl-2.0.html>.
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

/*
 The default match criteria, since version 0.9.0, is to match on
 correct MPEG2 TS packets.  Previously (<= 0.8.0) a rule would only
 match when a drop were detected.

 Its still possible to match on drop detection, via the parameter
 "--match-drop" (which is default off).

 Stats on packet drops are available via the proc filesystem.  These
 drop detection stats can be disabled via inverting the parameter
 --drop-detect, eg. "! --drop-detect".

 Setting the --format parameter to rtp or raw demands a certain format.
*/
static const struct option mpeg2ts_mt_opts[] = {
	{.name = "name",		.has_arg = true,  .val = 'n'},
	{.name = "drop-detect", 	.has_arg = false, .val = 'd'},
	{.name = "match-drop",		.has_arg = false, .val = 'm'},
	{.name = "max-streams",		.has_arg = true,  .val = 'x'},
	{.name = "format",              .has_arg = true,  .val = 'f'},
	{NULL},
};

static void mpeg2ts_mt_help(void)
{
	printf(
"mpeg2ts (MPEG2 Transport Stream) match options:\n"
"VERSION %s\n"
"   [--name <name>]          Name for proc file /proc/net/xt_mpeg2ts/rule_NAME\n"
"   [--match-drop]           Match on lost TS frames (default: off)\n"
"   [--drop-detect]          Detect TS frame loss and store stats (default: ON)\n"
"   [--max-streams <num>]    Track 'max' number of streams (per rule)\n"
"   [--format {auto|rtp|raw} Encapsulation format (default: auto)\n",
		version
		);
}

static void mpeg2ts_mt_init(struct xt_entry_match *match)
{
	struct xt_mpeg2ts_mtinfo *info = (void *)match->data;
	/* Enable drop detection per default */
	info->flags = XT_MPEG2TS_DETECT_DROP;

	/* Match on drops is disabled per default */
	/*  XT_MPEG2TS_MATCH_DROP */

	/* Auto-detect format */
	info->flags |= XT_MPEG2TS_FORMAT_AUTO;
}

static int mpeg2ts_mt_parse(int c, char **argv, int invert, unsigned int *flags,
			    const void *entry, struct xt_entry_match **match)
{
	struct xt_mpeg2ts_mtinfo *info = (void *)(*match)->data;
	uint32_t num;

	switch (c) {
	case 'n': /* --name */
		xtables_param_act(XTF_ONLY_ONCE, "mpeg2ts", "--name",
				  *flags & XT_MPEG2TS_PARAM_NAME);
		if (invert)
			xtables_error(PARAMETER_PROBLEM, "Inverting name?");
		if (strlen(optarg) == 0)
			xtables_error(PARAMETER_PROBLEM, "Zero-length name?");
		if (strchr(optarg, '"') != NULL)
			xtables_error(PARAMETER_PROBLEM,
				      "Illegal character in name (\")!");
		strncpy(info->rule_name, optarg, sizeof(info->rule_name));
		info->flags |= XT_MPEG2TS_PARAM_NAME;
		*flags |= XT_MPEG2TS_PARAM_NAME;
		break;

	case 'd': /* --drop-detect */
		if (*flags & XT_MPEG2TS_DETECT_DROP)
			xtables_error(PARAMETER_PROBLEM,
			      "Can't specify --drop-detect option twice");
		*flags |= XT_MPEG2TS_DETECT_DROP;

		if (invert)
			info->flags &= ~XT_MPEG2TS_DETECT_DROP;
		else
			info->flags |= XT_MPEG2TS_DETECT_DROP;

		break;

	case 'm': /* --match-drop */
		if (*flags & XT_MPEG2TS_MATCH_DROP)
			xtables_error(PARAMETER_PROBLEM,
			      "Can't specify --match-drop option twice");
		*flags |= XT_MPEG2TS_MATCH_DROP;

		if (invert)
			info->flags &= ~XT_MPEG2TS_MATCH_DROP;
		else
			info->flags |= XT_MPEG2TS_MATCH_DROP;

		break;

	case 'x': /* --max-streams */
		if (*flags & XT_MPEG2TS_MAX_STREAMS)
			xtables_error(PARAMETER_PROBLEM,
				"Can't specify --max-streams option twice");
		*flags |= XT_MPEG2TS_MAX_STREAMS;

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
				      "bad --max-streams: `%s'", optarg);

		/* DEBUG: printf("--max-stream=%lu\n", num); */
		info->flags |= XT_MPEG2TS_MAX_STREAMS;
		info->cfg.max = num;

		break;

	case 'f': /* --format */
		if (*flags & XT_MPEG2TS_FORMAT)
			xtables_error(PARAMETER_PROBLEM,
				"Can't specify --format option twice");
		*flags |= XT_MPEG2TS_FORMAT;

		if (strcmp(optarg, "auto") == 0)
			info->flags = (info->flags & ~XT_MPEG2TS_FORMAT) | XT_MPEG2TS_FORMAT_AUTO;
		else if (strcmp(optarg, "rtp") == 0)
			info->flags = (info->flags & ~XT_MPEG2TS_FORMAT) | XT_MPEG2TS_FORMAT_RTP;
		else if (strcmp(optarg, "raw") == 0)
			info->flags = (info->flags & ~XT_MPEG2TS_FORMAT) | XT_MPEG2TS_FORMAT_RAW;
		else
			xtables_error(PARAMETER_PROBLEM, "bad --format argument: `%s'", optarg);
		break;

	default:
		return false;
	}

	return true;
}

static void mpeg2ts_mt_print(const void *entry,
			     const struct xt_entry_match *match, int numeric)
{
	const struct xt_mpeg2ts_mtinfo *info = (const void *)(match->data);

	/* Always indicate this is a mpeg2ts match rule */
	printf("mpeg2ts match");

	if (info->flags & XT_MPEG2TS_PARAM_NAME)
		printf(" name:\"%s\"", info->rule_name);

	if (!(info->flags & XT_MPEG2TS_DETECT_DROP))
		printf(" !drop-detect");

	if ((info->flags & XT_MPEG2TS_MATCH_DROP))
		printf(" match-drop");

	if (info->flags & XT_MPEG2TS_MAX_STREAMS)
		printf(" max-streams:%u", info->cfg.max);

	switch (info->flags & XT_MPEG2TS_FORMAT) {
	case XT_MPEG2TS_FORMAT_RTP:
		printf(" format:rtp");
		break;
	case XT_MPEG2TS_FORMAT_RAW:
		printf(" format:raw");
		break;
	}
}

static void mpeg2ts_mt_save(const void *entry,
			    const struct xt_entry_match *match)
{
	const struct xt_mpeg2ts_mtinfo *info = (const void *)(match->data);

	/* We need to handle --name, --drop-detect, and --max-streams. */
	if (info->flags & XT_MPEG2TS_PARAM_NAME)
		printf(" --name \"%s\"",  info->rule_name);

	if (!(info->flags & XT_MPEG2TS_DETECT_DROP))
		printf(" ! --drop-detect");

	if (info->flags & XT_MPEG2TS_MATCH_DROP)
		printf(" --match-drop");

	if (info->flags & XT_MPEG2TS_MAX_STREAMS)
		printf(" --max-streams %u", info->cfg.max);

	switch (info->flags & XT_MPEG2TS_FORMAT) {
	case XT_MPEG2TS_FORMAT_RTP:
		printf(" --format rtp");
		break;
	case XT_MPEG2TS_FORMAT_RAW:
		printf(" --format raw");
		break;
	}
}

static struct xtables_match mpeg2ts_mt_reg = {
	.version        = XTABLES_VERSION,
	.name           = "mpeg2ts",
	.revision       = 0,
	.family         = PF_UNSPEC,
	.size           = XT_ALIGN(sizeof(struct xt_mpeg2ts_mtinfo)),
	.userspacesize  = offsetof(struct xt_mpeg2ts_mtinfo, hinfo),
	.init           = mpeg2ts_mt_init,
	.help           = mpeg2ts_mt_help,
	.parse          = mpeg2ts_mt_parse,
/*	.final_check    = mpeg2ts_mt_check,*/
	.print          = mpeg2ts_mt_print,
	.save           = mpeg2ts_mt_save,
	.extra_opts     = mpeg2ts_mt_opts,
};

static void _init(void)
{
	xtables_register_match(&mpeg2ts_mt_reg);
}
