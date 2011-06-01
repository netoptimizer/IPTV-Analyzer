package IPTV::Analyzer::Config;

=head1 NAME

IPTV::Analyzer::Config - module for reading config file "collector.conf"

=head1 SYNOPSIS

The IPTV::Analyzer::Config module is a helper module for reading
config file "collector.conf".  Notice, also loads log4perl.conf
config file.

=cut

use strict;
use warnings;
use Carp;

use Config::File;
use Data::Dumper;

use File::Basename; # dirname() for config file loading

BEGIN {
     use Exporter ();
     our ($VERSION, @ISA, @EXPORT, @EXPORT_OK, %EXPORT_TAGS);

     # Package version
     $VERSION     = $IPTV::Analyzer::Version::VERSION;

     @ISA         = qw(Exporter);
     @EXPORT      = qw(
                       get_config
                       lookup_event
                       lookup_severity
                      );
}

INIT {
    # Auto load the config, when this module is loaded.
    # This is done due to the mpeg2ts module, and how it access its $cfg
    new();
}

# Global var
our $singleton_cfg = undef;

# Simply define event_types here
#  TODO: Replace with select from Database.
#  TODO: Move into object
our $global_event_types = {
    'unknown'    =>   0, # Unknown event name lookup
    'new_stream' =>   1, # New stream detected
    'drop'       =>   2, # Drops detected, both skips and discon
    'no_signal'  =>   4, # Stream have stopped transmitting data
#   'ok_signal', =>   8, # (NOT USED, see severity instead)
    'ttl_change' =>  16, # Indicate TTL changed
    'transition' =>  32, # The event_state changed since last poll
    'heartbeat'  =>  64, # Heartbeat event to monitor status
    'invalid'    => 128, # Some invalid event situation arose
};

# severity levels taken from ZenOSS
#   http://community.zenoss.org/docs/DOC-4766#d0e6170
#   Number	Name		Color in ZenOSS
#       0	Clear		Green
#       1	Debug		Grey
#       2	Info		Blue
#       3	Warning		Yellow
#       4	Error		Orange
#       5	Critical	Red
#
our $global_severity_levels = {
    'clear'    =>  0, # Clears the eventType
    'debug'    =>  1,
    'info'     =>  2,
    'warning'  =>  3,
    'error'    =>  4,
    'critical' =>  5
};

###
# Logging system
#
use Log::Log4perl qw(get_logger :levels);
our $logger = get_logger(__PACKAGE__);

# Load log4perl config.
#
#  Notice need to load config file for logging system before loading
#  the collector.conf.
#
# TODO: Change default/fallback config, to log to /var/log, as the
#  iptv-collector daemon breaks off stdout and stderr.
sub load_log4perl_config()
{
    # - fallback logger config
    my $logger_fallback_config = qq(
    log4perl.logger = INFO, Screen1
#    log4perl.logger = DEBUG, Screen1
    log4perl.appender.Screen1 = Log::Log4perl::Appender::ScreenColoredLevels
#    log4perl.appender.Screen1.stderr = 1
    log4perl.appender.Screen1.stderr = 0
    log4perl.appender.Screen1.Threshold = DEBUG
#    log4perl.appender.Screen1.Threshold = WARN
    log4perl.appender.Screen1.layout = Log::Log4perl::Layout::SimpleLayout
    );
    # - load logger config if available, try different locations
    my $log4perl_conf="log4perl.conf";
    my $basedir = dirname($0);
    if ( -e "${basedir}/${log4perl_conf}" ) {
	# Try local script dir
	Log::Log4perl->init("${basedir}/${log4perl_conf}");
    } elsif ( -e "/etc/iptv-analyzer/${log4perl_conf}" ) {
	# Try /etc/iptv-analyzer
	Log::Log4perl->init("/etc/iptv-analyzer/${log4perl_conf}");
    } elsif ( -e "/etc/${log4perl_conf}" ) {
	# Try /etc/
	Log::Log4perl->init("/etc/${log4perl_conf}");
    } else {
	# Use fallback config
	my $log = "Could NOT find the log4perl config file:[$log4perl_conf]";
	$log.= " - you will not see daemon log info!!!";
	Log::Log4perl->init(\$logger_fallback_config);
	$logger->error($log);
    }
}


###
# Helper functions
#
sub lookup_event($)
{
    my $event_name = shift;
    my $event_type;
    if (exists        $global_event_types->{"$event_name"} ) {
	$event_type = $global_event_types->{"$event_name"};
    } else {
	$logger->logcarp("Eventname $event_name, is unknown");
	$event_type = $global_event_types->{"unknown"};
    }
    return $event_type;
}

sub lookup_severity($)
{
    my $name = shift;
    my $type;
    if (exists  $global_severity_levels->{"$name"} ) {
	$type = $global_severity_levels->{"$name"};
    } else {
	$logger->logcarp("Severity name $name, is unknown, using level debug");
	$type = $global_severity_levels>{"debug"};
    }
    return $type;
}

###
# Object related methods
#

# Create a new config object.
sub new()
{
    my $invocant = shift;
    my $class    = ref($invocant) || $invocant;

    # Need to load the log4perl.conf first
    load_log4perl_config();

    # Implement singleton object, to avoid loading config several times
    if (defined($singleton_cfg)) {
	$logger->logcarp("Config already loaded, returning current config."
	    . " Use get_config() instead");
	return $singleton_cfg;
    }
    else {
	$logger->info("Loading config.");
    }

    # Loading config
    my $self = load_config();

    #my $self = {};
    if ($class) {
	bless($self, $class);
    } else {
	bless($self);
    }

    $self->validate_config(); # needs logging module loaded

    # Store singelton object
    $singleton_cfg = $self;

    return $self;
}

###
# Config file processing
#

sub load_config {
    my $cfgfile = shift || "collector.conf";
    my $cfgdir;
    my $cfgdir_default = "/etc/iptv-analyzer/";
    if ( -e "${cfgdir_default}${cfgfile}" ) {
	$cfgdir = $cfgdir_default;
    } else {
	# Use the config from the dir of the script
	$cfgdir = dirname($0);
	my $l = "Cannot find config file ${cfgdir_default}${cfgfile} - ";
	   $l.= "instead use config from dir of script: ${cfgdir}/${cfgfile}";
	$logger->warn($l);
    }
    my $cfg = Config::File::read_config_file("${cfgdir}/${cfgfile}");
    return $cfg;
}

sub get_config {
    my $self = shift;
    if (defined($self)) {
	return $self;
    } else {
	if (defined($singleton_cfg)) {
	    return  $singleton_cfg;
	} else {
	    # Create and load config if it didn't exist
	    my $log="Force loading config, you didn't load it earlier";
	    carp("${log}, its needed"); # log4perl not init'ed yet
	    return new();
	}
    }
}

# Example of the $cfg hash being validated
#my $cfg_example = {
#    'probe_ip'   => '10.10.10.42',
#    'probe_name' => 'tvprobe42',
#    'input' => {
#	'rule_eth42' => {
#	    'procfile'   => '/proc/net/xt_mpeg2ts/rule_test',
#	    'shortloc'   => 'alb',
#	    'switch'     => 'albcs35',
#	    'name'       => 'Main signal',
#	    'distance'   => '1',
#	    'location'   => 'Albertslund Serverrum B',
#	    'address'    => 'Herstedvang 8, 2620 Albertslund',
#	    'switchport' => 'e0/0',
#	    'switchtype' => 'Foundry FLS',
#	    'input_ip'   => '192.168.16.42',
#	    'input_dev'  => 'eth42',
#	    'hidden'     => 'no',
#	    }
#    },
#    'dbhost' => 'tvprobe004.comx.dk',
#    'dbpass' => 'thepassword',
#    'dbname' => 'tvprobedb',
#    'dbuser' => 'tvprobe'
#};

sub validate_config {
    my $cfg = shift;
    my $res = 1;

    # Check main identification values are defined
    if ( not exists $cfg->{'probe_ip'} ) {
	$logger->logcroak("Config missing 'probe_ip'");
    }
    if ( not exists $cfg->{'probe_name'} ) {
	$logger->logcroak("Config missing 'probe_name'");
    }

    # Check config defines what files to read
    if ( not exists $cfg->{'input'} ) {
	$logger->logcroak("Config does not contain any input files");
    }

    # Check input contain elements (it must not be a simple value)
    my $inputs = $cfg->{'input'};
    if (ref($inputs) ne 'HASH') {
	my $log = "Config is invalid, 'input' must not be a simple value";
	$logger->logcroak($log);
	# Must die, else Perl dies when using deref of hash later
    }

    # Walk through each input hash value
    foreach my $key (keys %{$inputs}) {
	my $input = $inputs->{$key};
	my $log   = "input[$key]";

	if (ref($input) ne 'HASH') {
	    my $l = "Config is invalid, 'input[$key]' is a simple value";
	    $l   .= " (OLD STYLE CONFIG?)";
	    $logger->logcroak($l);
	    # Must die, else Perl dies when using deref of hash later
	}

	# Main input file
	if (exists $input->{'procfile'}) {
	    my $file = $input->{'procfile'};
	    if ( ! -e "$file" ) {
		$logger->fatal("${log}[procfile]=$file file does not exists!");
		$res = 0;
	    }
	    $logger->debug("${log}[procfile]=$file file exists");
	} else {
	    $logger->logcroak("Option ${log}[procfile] is required!");
	}

	# Required identifiers
	if (not exists $input->{'shortloc'}) {
	    my $l = "Config ${log}[shortloc] is a required identifier!";
	    $logger->logcroak($l);
	}
	if (not exists $input->{'switch'}) {
	    my $l = "Config ${log}[switch] is a required identifier!";
	    $logger->logcroak($l);
	}

	# Construct a "description" if not configured
	if (not exists $input->{'description'}) {
	    my $desc = "";
	    $desc .= $cfg->{'probe_name'};
	    if (exists $input->{'location'}) {
		$desc .= " at " . $input->{'location'};
	    } else {
		$desc .= " at " . $input->{'shortloc'};
	    }
	    $input->{'description'} = $desc;
	}

	# Optional settings, initialize if empty
	$input->{'distance'} = -1 if (not exists $input->{'distance'});
	$input->{'location'} = "Unknown" if (not exists $input->{'location'});
	$input->{'address'}  = "Unknown" if (not exists $input->{'address'});
	$input->{'switchport'} = "" if (not exists $input->{'switchport'});
	$input->{'switchtype'} = "" if (not exists $input->{'switchtype'});
	$input->{'input_ip'}   = "" if (not exists $input->{'input_ip'});
	$input->{'input_dev'}  = "" if (not exists $input->{'input_dev'});

	# Handling the option "hidden"
	my $hidden_default = "no";
	if (exists $input->{'hidden'}) {
	    if (($input->{'hidden'} ne "yes") && ($input->{'hidden'} ne "no")) {
		my $l = "Invalid 'hidden' config value:[";
		$l   .= $input->{'hidden'} . "] using $hidden_default instead";
		$logger->error($l);
		$input->{'hidden'} = $hidden_default;
	    }
	} else {
	    # Default setting if not set
	    $input->{'hidden'} = $hidden_default;
	}
    }

    # DB config checks
    $logger->logcroak("Config need 'dbhost'") if (not exists $cfg->{'dbhost'});
    $logger->logcroak("Config need 'dbpass'") if (not exists $cfg->{'dbpass'});
    $logger->logcroak("Config need 'dbname'") if (not exists $cfg->{'dbname'});
    $logger->logcroak("Config need 'dbuser'") if (not exists $cfg->{'dbuser'});

    return $res;
}

###
# Accessing elements
#
#  Due to historical reasons, the mpeg2ts module simply access
#  elements directly, using the module as an hash.
#
#  But for future usage, we want to be are bit more "clean" and
#  provide helper functions to access elements in the config "object".
#
sub get_probe_ip()
{
    my $self = shift;
    my $value = $self->{'probe_ip'};
    return $value;
}

sub get_probe_name()
{
    my $self = shift;
    my $value = $self->{'probe_name'};
    return $value;
}

# Part of $cfg
#my $cfg_example2 = {
#    'probe_ip'   => '10.10.10.42',
#    'probe_name' => 'tvprobe42',
#    'input' => {
#	'inputKey' => {
#	    'valueKey'   => 'albcs35',
#	    'procfile'   => '/proc/net/xt_mpeg2ts/rule_test',
#	    'shortloc'   => 'alb',
#
sub get_input_value()
{
    my $self     = shift;
    my $inputKey = shift;
    my $valueKey = shift;

    my $value="";
    if (exists     $self->{'input'}->{"$inputKey"}) {
	if (exists $self->{'input'}->{"$inputKey"}->{"$valueKey"}) {
	    $value=$self->{'input'}->{"$inputKey"}->{"$valueKey"};
	} else {
	    my $txtcfg = "input[$inputKey][$valueKey]";
	    $logger->warn("No config setting available for $txtcfg");
	}
    } else {
	#my $txtcfg = "input[$inputKey] cannot read $valueKey";
	#$logger->warn("No config for $txtcfg");
    }
    return $value;
}



1;
__END__
# Below is documentation for the module.
#  One way of reading it: "perldoc IPTV/Analyzer/Config.pm"

=head1 DESCRIPTION

 Config file being read and validated: /etc/iptv-analyzer/collector.conf

=head1 DEPENDENCIES

This module uses the module L<Config::File> for parsing input files.

=head1 AUTHOR

Jesper Dangaard Brouer, E<lt>hawk@comx.dkE<gt> or E<lt>hawk@diku.dkE<gt>.

=head1 COPYRIGHT AND LICENSE

Copyright (C) 2009-2011+ by Jesper Dangaard Brouer, ComX Networks A/S.

This file is licensed under the terms of the GNU General Public
License 2.0. or newer. See <http://www.gnu.org/licenses/gpl-2.0.html>.

=cut
