#!/usr/bin/perl -w -I../lib/
#
# Simple program for testing the SNMP trap facilities
#  without having to generate the error situations.
#
use strict;
use warnings;

use IPTV::Analyzer::mpeg2ts;
use IPTV::Analyzer::snmptrap;

print "open_snmp_session()\n";
open_snmp_session("127.0.0.1", "public");

print "send_snmptrap()\n";
send_snmptrap(4, "no-signal", "224.0.1.2", "192.168.1.1");

print "close_snmp_session()\n";
close_snmp_session();

#print "send_snmptrap()\n";
#send_snmptrap();

print "END\n";
