Name:			pg_stats_reporter
Version:		1.0.1
Release:		1%{?dist}
Summary:		Graphical viewer for pg_statsinfo
Summary(ja):	pg_statsinfo 用グラフィカルビューア
Group:			Applications/Databases
License:		BSD
URL:			http://pgstatsinfo.projects.pgfoundry.org/index_ja.html
Packager:		NIPPON TELEGRAPH AND TELEPHONE CORPORATION
Source0:		pg_stats_reporter-1.0.1.tar.gz
BuildRoot:		%{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
BuildArch:		noarch
Requires:		php >= 5.3.3, php-pgsql >= 5.3.3
Requires:		php-common >= 5.3.3, php-intl >= 5.3.3
Requires:		httpd >= 2.2
AutoReqProv: 0

%description

%prep
%setup -q %{name}

%install
/bin/mkdir -p %{buildroot}/var/www
/bin/cp -r html %{buildroot}/var/www
/bin/cp -r pg_stats_reporter %{buildroot}/var/www
/bin/cp LICENSE %{buildroot}/var/www/pg_stats_reporter/
/bin/cp history.ja %{buildroot}/var/www/pg_stats_reporter/

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
/var/www/html/pg_stats_reporter/
/var/www/pg_stats_reporter/pg_stats_reporter.ini.sample
/var/www/pg_stats_reporter/lib/
/var/www/pg_stats_reporter/template/
/var/www/pg_stats_reporter/message/
/var/www/pg_stats_reporter/LICENSE
/var/www/pg_stats_reporter/history.ja

%defattr(755,apache,apache,-)
%dir /var/www/pg_stats_reporter/cache
%dir /var/www/pg_stats_reporter/compiled

%post
if [ $1 = 1 ] && [ ! -e /var/www/pg_stats_reporter/pg_stats_reporter.ini ] ; then
/bin/cp /var/www/pg_stats_reporter/pg_stats_reporter.ini.sample /var/www/pg_stats_reporter/pg_stats_reporter.ini
fi

%postun
/bin/rm -rf /var/www/pg_stats_reporter/cache
/bin/rm -rf /var/www/pg_stats_reporter/compiled

%changelog
* Fri Feb   1 2013 - NTT OSS Center 1.0.1-1
- Fix some bugs
* Fri Nov  30 2012 - NTT OSS Center 1.0.0-1
- pg_stats_reporter 1.0.0 released
