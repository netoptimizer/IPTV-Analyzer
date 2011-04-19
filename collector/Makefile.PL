use ExtUtils::MakeMaker;

# EXE_FILES is a bit strange, we need it getting the bin/ files
# copied, but is also means that these files are getting copied to the
# dir blib/script/ (which I dont understand the purpose of).
our @exe_files;
#push @exe_files, 'bin/iptv-collector';

# Automatically find files in "bin" directory
my $bindir="bin";
opendir (DIR, $bindir) or warn "Couldn't open directory, $!";
while ($file = readdir(DIR))
{
    next if ($file =~ m/^\./);
    print "Found bin file: $file\n";
    push @exe_files, "$bindir/$file";
}
closedir DIR;

# Add some extra to the generated Makefile
{
    package MY;
    sub postamble {
	'
INST_ETC = /etc/iptv-analyzer

install::
	$(NOECHO) $(ECHO) Installing sample config in: $(INST_ETC)
	$(NOECHO) test -d $(INST_ETC) || $(MKPATH) $(INST_ETC)
	$(CP) etc/log4perl.conf.sample  $(INST_ETC)/
	$(CP) etc/collector.conf.sample $(INST_ETC)/
'
    }
}


my %attr = (
    NAME      => 'iptv-collector',
#    VERSION   => '0.x.x',
    VERSION_FROM      => 'lib/IPTV/Analyzer/mpeg2ts.pm', # finds $VERSION
    PREREQ_PM => {
	'Log::Log4perl'   => 0,
	'Config::File'    => 0,
	'Proc::Daemon'    => 0,
	'Proc::PID::File' => 0,
	'Data::Compare'   => 0,
	'File::Basename'  => 0,
	'DBI'             => 0,
	'DBD::mysql'      => 0,
	'Log::Dispatch::Email.pm' => 0,
    },
    PL_FILES  => {},    # Prevent old MakeMaker from running Build.PL
    EXE_FILES => \@exe_files,
#    'dist'    => {COMPRESS => 'gzip', SUFFIX => 'gz'},
#   realclean => {FILES => join ' ', @clean_files},
    AUTHOR    => 'Jesper Dangaard Brouer',
    ABSTRACT  => 'IPTV-analyzer mpeg2ts Collector daemon',
#    INSTALLDIRS => 'site',
);

(my $mmv = ExtUtils::MakeMaker->VERSION) =~ s/_//g;
$mmv >= 6.31 and $attr{LICENSE} = 'GPL';

WriteMakefile (%attr);
