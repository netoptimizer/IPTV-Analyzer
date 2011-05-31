package IPTV::Analyzer::snmptrap;

=head1 NAME

IPTV::Analyzer::snmptrap - module for sending SNMP traps events

=head1 SYNOPSIS

The IPTV::Analyzer::snmptrap module is a helper module for sending
SNMP traps, when e.g. events like no-signal occurs.

=cut

use strict;
use warnings;

use Net::SNMP qw(:ALL);

#use Config::File;
use Data::Dumper;

use Log::Log4perl qw(get_logger :levels);
our $logger = get_logger(__PACKAGE__);

use IPTV::Analyzer::Config;

BEGIN {
     use Exporter ();
     our ($VERSION, @ISA, @EXPORT, @EXPORT_OK, %EXPORT_TAGS);

     # Package version
     $VERSION     = $IPTV::Analyzer::Version::VERSION;

     @ISA         = qw(Exporter);
     @EXPORT      = qw(
                       open_snmp_session
                       close_snmp_session
                       send_snmptrap
                      );

}

# Global vars
our $snmp_session = undef;

sub open_snmp_session($$)
{
    my $host      = shift;
    my $community = shift;
    my $version   = 2;

    ($snmp_session, my $error) =
	Net::SNMP->session(
	    -hostname  => $host,
	    -version   => $version,
	    -community => $community,
	    -port      => SNMP_TRAP_PORT
	);

    if (!defined($snmp_session)) {
	$logger->fatal("Cannot start a SNMP session: $error");
	return 0;
	#exit 1
    }
    return 1;
}

sub close_snmp_session()
{
    $snmp_session->close();
    $snmp_session = undef;
}

# taken from snmptrapd-sendtest.pl / Net-SNMPTrapd-0.04
#our %opt;
#$opt{version}   = $opt{version}   || 2;
#$opt{community} = $opt{community} || 'public';
#$opt{integer}   = $opt{integer}   || 1;
#$opt{string}    = $opt{string}    || 'String';
#$opt{oid}       = $opt{oid}       || '1.2.3.4.5.6.7.8.9';
#$opt{ip}        = $opt{ip}        || '10.10.10.1';
#$opt{counter32} = $opt{counter32} || 32323232;
#$opt{gauge32}   = $opt{gauge32}   || 42424242;
#$opt{timeticks} = $opt{timeticks} || time();
#$opt{opaque}    = $opt{opaque}    || 'opaque data';
#$opt{inform}    = $opt{inform}    || 0;


sub send_snmptrap($$$$)
{
    my $event_type = shift || 0;
    my $event_name = shift || "please-implement";
    my $multicast  = shift;
    my $src_ip     = shift;

    my $cfg = get_config();

    if ( !defined($snmp_session) ) {
	$logger->fatal("Cannot send SNMPtrap, no snmp session opened!");
	return 0;
    }

    # FIXME: get data for options
    # General config for collector id:
    my $probe_ip   = "1.2.3.4"; # $cfg{'probe_ip'}
    my $probe_name = "probe_name"; # $cfg{'probe_name'}
    #
    # Specific config for input this trap concerns:
    my $inputKey      = "rule_eth42";
    my $inputShortloc = "cph";
    my $inputSwitch   = "cphcs1";

    my $streamNoSignal = '1.3.6.1.4.1.26124.43.2.2.2';
    my $trap = $streamNoSignal;

    my $result = $snmp_session->snmpv2_trap(
    -varbindlist  => [
	 # First two is required options
	 # FIXME: Change TIMETICKS to correct mpeg2ts "uptime"
	 '1.3.6.1.2.1.1.3.0',         TIMETICKS,         time(),
	 '1.3.6.1.6.3.1.1.4.1.0',     OBJECT_IDENTIFIER, $trap,

	 # CollectorId
	 '1.3.6.1.4.1.26124.43.1.1',  IPADDRESS,  $probe_ip,
	 # CollectorName
	 '1.3.6.1.4.1.26124.43.1.2',  OCTET_STRING,  $probe_name,

	 # eventType -- Event type: noSignal(4) / okSignal(8)
	 '1.3.6.1.4.1.26124.43.2.1.1.1', INTEGER, $event_type,

	 # eventName
	 '1.3.6.1.4.1.26124.43.2.1.1.2', OCTET_STRING, $event_name,

	 # multicastDest
	 '1.3.6.1.4.1.26124.43.2.1.2.1', IPADDRESS, $multicast,

	 # streamerSource
	 '1.3.6.1.4.1.26124.43.2.1.2.2', IPADDRESS, $src_ip,

	 # inputKey,     -- The collectors input[key]
	 '1.3.6.1.4.1.26124.43.2.1.3.1', OCTET_STRING, $inputKey,
	 # inputShortloc
	 '1.3.6.1.4.1.26124.43.2.1.3.2', OCTET_STRING, $inputShortloc,
	 # inputSwitch
	 '1.3.6.1.4.1.26124.43.2.1.3.3', OCTET_STRING, $inputSwitch,

	 # Also report the NIC interface?  IF-MIB::ifIndex
	 #"1.3.6.1.2.1.2.2.1.1.$ifIndex", INTEGER,    $ifIndex,

# Example of datatypes
#	'1.3.6.1.4.1.26124.42.3.3',  INTEGER,           $opt{integer},
#	'1.3.6.1.4.1.26124.42.3.4',  OCTET_STRING,      $opt{string},
#	'1.3.6.1.4.1.26124.42.3.5',  OBJECT_IDENTIFIER, $opt{oid},
#	'1.3.6.1.4.1.26124.42.3.6',  IPADDRESS,         $opt{ip},
#	'1.3.6.1.4.1.26124.42.3.7',  COUNTER32,         $opt{counter32},
#	'1.3.6.1.4.1.26124.42.3.8',  GAUGE32,           $opt{gauge32},
#	'1.3.6.1.4.1.26124.42.3.9',  TIMETICKS,         $opt{timeticks},
#	'1.3.6.1.4.1.26124.42.3.10', OPAQUE,            $opt{opaque}
	] );

    return $result;
}

1;
__END__
# Below is documentation for the module.
#  One way of reading it: "perldoc IPTV/Analyzer/mpeg2ts.pm"

=head1 DESCRIPTION

The MIB is defined in snmp/mibs/IPTV-ANALYZER-MIB.txt

=head1 DEPENDENCIES

This module uses the module L<Net::SNMP> for sending snmptraps.

=head1 AUTHOR

Jesper Dangaard Brouer, E<lt>hawk@comx.dkE<gt> or E<lt>hawk@diku.dkE<gt>.

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2009-2011+ by Jesper Dangaard Brouer, ComX Networks A/S.

This file is licensed under the terms of the GNU General Public
License 2.0. or newer. See <http://www.gnu.org/licenses/gpl-2.0.html>.

=cut
