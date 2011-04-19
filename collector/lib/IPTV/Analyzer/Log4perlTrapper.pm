#
# Trick to catch stderr messages from e.g. DB calls into Log4perl
#
# From:
#
# http://search.cpan.org/~mschilli/Log-Log4perl-1.26/lib/Log/Log4perl/FAQ.pm#Some_module_prints_messages_to_STDERR._How_can_I_funnel_them_to_Log::Log4perl?
#

########################################
package IPTV::Analyzer::Log4perlTrapper;
########################################

use Log::Log4perl qw(:easy);

sub TIEHANDLE {
    my $class = shift;
    bless [], $class;
}

sub PRINT {
    my $self = shift;
    $Log::Log4perl::caller_depth++;
    ERROR @_;
    $Log::Log4perl::caller_depth--;
}

#sub DESTROY {
#    my $self = shift;
#    print @_;
#}

sub DESTROY {}

1;
