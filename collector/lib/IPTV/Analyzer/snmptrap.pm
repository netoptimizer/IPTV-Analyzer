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


# Which config file input[] source does this trap concern
sub construct_input_identification($)
{
    my $inputKey = shift;
    my $cfg = get_config();

    # Specific config for input this trap concerns
    my $inputShortloc = $cfg->get_input_value($inputKey, "shortloc");
    my $inputSwitch   = $cfg->get_input_value($inputKey, "switch");

    my @array = (
	# inputKey,     -- The collectors input[key]
	'1.3.6.1.4.1.26124.43.2.1.3.1', OCTET_STRING, $inputKey,
	# inputShortloc    input[key][shortloc]
	'1.3.6.1.4.1.26124.43.2.1.3.2', OCTET_STRING, $inputShortloc,
	# inputSwitch      input[key][switch]
	'1.3.6.1.4.1.26124.43.2.1.3.3', OCTET_STRING, $inputSwitch
    );
    return @array;
}

sub construct_probe_identification()
{
    my $cfg = get_config();
    my $probe_ip   = $cfg->get_probe_ip;
    my $probe_name = $cfg->get_probe_name;
    my @array = (
	# CollectorId
	'1.3.6.1.4.1.26124.43.1.1',  IPADDRESS,  $probe_ip,
	# CollectorName
	'1.3.6.1.4.1.26124.43.1.2',  OCTET_STRING,  $probe_name,
    );
    return @array;
}

sub construct_trap_oid($$)
{
    my $trap_oid  = shift;
    my $timeticks = shift || 0;

    # The first two variable-bindings fields in the snmpV2-trap are
    # specified by SNMPv2 and should be:
    #  sysUpTime.0   - ('1.3.6.1.2.1.1.3.0',     TIMETICKS, $timeticks)
    #  snmpTrapOID.0 - ('1.3.6.1.6.3.1.1.4.1.0', OBJECT_IDENTIFIER, $oid)

    my @array = (
	 '1.3.6.1.2.1.1.3.0',         TIMETICKS,         $timeticks,
	 '1.3.6.1.6.3.1.1.4.1.0',     OBJECT_IDENTIFIER, $trap_oid,
	);
    return @array;
}

sub construct_event($$$)
{
    my $event_type     = shift;
    my $event_name     = shift;
    my $event_severity = shift;
    my @array = (
	# eventType
	'1.3.6.1.4.1.26124.43.2.1.1.1', INTEGER, $event_type,
	# eventName
	'1.3.6.1.4.1.26124.43.2.1.1.2', OCTET_STRING, $event_name,
	# eventSeverity (indicate clear/ok signal)
	'1.3.6.1.4.1.26124.43.2.1.1.3', INTEGER, $event_severity,
	);
    return @array;
}

sub construct_event_no_signal()
{
    my $event_name     = "no_signal";
    my $event_type     = lookup_event($event_name);
    my $event_severity = "5";
    my @array = construct_event($event_type, $event_name, $event_severity);
    return @array;
}

sub construct_event_no_signal_clear()
{
    my $event_name     = "no_signal";
    my $event_type     = lookup_event($event_name);
    my $event_severity = "0"; # CLEARs no_signal
    my @array = construct_event($event_type, $event_name, $event_severity);
    return @array;
}

sub construct_event_via_name($$)
{
    my $event_name     = shift;
    my $event_severity = shift;
    my $event_type     = lookup_event($event_name);
    my @array = construct_event($event_type, $event_name, $event_severity);
    return @array;
}

our $global_trap_oids = {
    'no_signal'      => '1.3.6.1.4.1.26124.43.2.2.2', # MIB: streamNoSignal
    'generalEvent'   => '1.3.6.1.4.1.26124.43.2.2.1',
    'unknown'        => '1.3.6.1.4.1.26124.43',
};

sub lookup_trap($)
{
    my $trapname = shift;
    my $oid;
    my $result=0;
    if (exists $global_trap_oids->{"$trapname"} ) {
	$oid = $global_trap_oids->{"$trapname"};
	$result = 1;
    } else {
	$oid = $global_trap_oids->{"unknown"};
    }
    #return ($oid, $result);
    return $oid;
}

sub send_snmptrap($$$$$)
{
    my $event_name = shift;
    my $severity   = shift;
    my $inputkey   = shift;
    my $multicast  = shift;
    my $src_ip     = shift;

    my $cfg = get_config();

    if ( !defined($snmp_session) ) {
	$logger->fatal("Cannot send SNMPtrap, no snmp session opened!");
	return 0;
    }

    # The first two required variable-bindings fields in snmpV2-trap
    my $trap = lookup_trap("no_signal");
    my @trap_oid = construct_trap_oid($trap, 0);

    # The event type
    #my @event_oids = construct_event_no_signal($severity);
    my @event_oids = construct_event_via_name($event_name, $severity);

    # General identification of the probe
    my @ident_probe = construct_probe_identification();

    # Specific identification of config input[key]
    my @ident_input = construct_input_identification($inputkey);

    # TODO: Stream identification

    my @oid_array =
	(
	 @trap_oid,
	 @ident_probe,
	 @event_oids,

	 # multicastDest
	 '1.3.6.1.4.1.26124.43.2.1.2.1', IPADDRESS, $multicast,
	 # streamerSource
	 '1.3.6.1.4.1.26124.43.2.1.2.2', IPADDRESS, $src_ip,

	 @ident_input
	);


    # Check oid_array for undef's as snmpv2_trap cannot handle these
    for my $n (2 .. $#oid_array)
    {
	if (! defined $oid_array[$n] ) {
	    my $theoid = $oid_array[$n-2];
	    my $log="OID $theoid contains undef (SNMP trap will fail)";
	    $logger->error($log);
	}
    }

    my $result = $snmp_session->snmpv2_trap(
	-varbindlist  => \@oid_array
	);

    if(!$result) {
	#print "snmperror, input oid_array:" . Dumper(\@oid_array) . "\n";
	$logger->error("Could not send SNMP trap");
    }

    return $result;
}

# Example of datatypes
#	'1.3.6.1.4.1.26124.42.3.3',  INTEGER,           $opt{integer},
#	'1.3.6.1.4.1.26124.42.3.4',  OCTET_STRING,      $opt{string},
#	'1.3.6.1.4.1.26124.42.3.5',  OBJECT_IDENTIFIER, $opt{oid},
#	'1.3.6.1.4.1.26124.42.3.6',  IPADDRESS,         $opt{ip},
#	'1.3.6.1.4.1.26124.42.3.7',  COUNTER32,         $opt{counter32},
#	'1.3.6.1.4.1.26124.42.3.8',  GAUGE32,           $opt{gauge32},
#	'1.3.6.1.4.1.26124.42.3.9',  TIMETICKS,         $opt{timeticks},
#	'1.3.6.1.4.1.26124.42.3.10', OPAQUE,            $opt{opaque}


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
