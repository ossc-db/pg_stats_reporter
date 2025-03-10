Name:			pg_stats_reporter
Version:		17.0
Release:		1%{?dist}
Summary:		Graphical viewer for pg_statsinfo
Summary(ja):	pg_statsinfo 用グラフィカルビューア
Group:			Applications/Databases
License:		BSD
URL:			https://github.com/ossc-db/pg_stats_reporter/
Packager:		NIPPON TELEGRAPH AND TELEPHONE CORPORATION
Source0:		%{name}-%{version}.tar.gz
BuildRoot:		%{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
BuildArch:		noarch
Requires:		php >= 7.2.24, php-pgsql >= 7.2.24
Requires:		php-common >= 7.2.24, php-xml >= 7.2.24
Requires:		httpd >= 2.4
AutoReqProv: 0

%description

%prep
%setup -q %{name}

%install
/bin/mkdir -p %{buildroot}/var/www
/bin/mkdir -p %{buildroot}/usr/local/bin
/bin/cp -r html %{buildroot}/var/www
/bin/cp -r pg_stats_reporter_lib %{buildroot}/var/www
/bin/cp bin/pg_stats_reporter %{buildroot}/usr/local/bin
/bin/cp LICENSE %{buildroot}/var/www/pg_stats_reporter_lib/

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
/var/www/html/pg_stats_reporter/
/var/www/pg_stats_reporter_lib/pg_stats_reporter.ini.sample
/var/www/pg_stats_reporter_lib/module/
/var/www/pg_stats_reporter_lib/template/
/var/www/pg_stats_reporter_lib/message/
/var/www/pg_stats_reporter_lib/LICENSE

%defattr(755,apache,apache,-)
%dir /var/www/pg_stats_reporter_lib/cache
%dir /var/www/pg_stats_reporter_lib/compiled

%defattr(755,root,root,-)
/usr/local/bin/pg_stats_reporter

%post
if [ $1 = 1 ] && [ ! -e /etc/pg_stats_reporter.ini ] ; then
/bin/cp /var/www/pg_stats_reporter_lib/pg_stats_reporter.ini.sample /etc/pg_stats_reporter.ini
fi
/bin/rm -rf /var/www/pg_stats_reporter_lib/cache/*
/bin/rm -rf /var/www/pg_stats_reporter_lib/compiled/*

%postun
if [ $1 = 0 ] ; then
/bin/rm -rf /var/www/pg_stats_reporter_lib
fi
if [ $1 = 1 ] ; then
/bin/rm -rf /var/www/pg_stats_reporter_lib/cache/*
/bin/rm -rf /var/www/pg_stats_reporter_lib/compiled/*
fi

%changelog
* Wed Feb  19 2025 - NTT OSS Center 17.0-1
- pg_stats_reporter 17.0 released
* Thu Feb  29 2024 - NTT OSS Center 16.0-1
- pg_stats_reporter 16.0 released
* Fri Jun  30 2023 - NTT OSS Center 15.1-1
- pg_stats_reporter 15.1 released
* Wed Feb   1 2023 - NTT OSS Center 15.0-1
- pg_stats_reporter 15.0 released
* Tue Feb   1 2022 - NTT OSS Center 14.0-1
- pg_stats_reporter 14.0 released
* Mon Dec  14 2020 - NTT OSS Center 13.0-1
- pg_stats_reporter 13.0 released
* Fri Jan  24 2020 - NTT OSS Center 12.0-1
- pg_stats_reporter 12.0 released
* Tue Jan  22 2019 - NTT OSS Center 11.0-1
- pg_stats_reporter 11.0 released
* Thu Jan  25 2018 - NTT OSS Center 10.0-1
- pg_stats_reporter 10.0 released
* Tue Sep  12 2017 - NTT OSS Center 3.3.1-1
- Fix some bugs.
* Thu Jun  22 2017 - NTT OSS Center 3.3.0-1
- pg_stats_reporter 3.3.0 released
* Wed Nov  18 2015 - NTT OSS Center 3.2.0-1
- pg_stats_reporter 3.2.0 released
* Thu Jun  11 2015 - NTT OSS Center 3.1.0-1
- pg_stats_reporter 3.1.0 released
* Thu Jul  24 2014 - NTT OSS Center 3.0.0-1
- pg_stats_reporter 3.0.0 released
* Fri Oct  25 2013 - NTT OSS Center 2.0.0-1
- pg_stats_reporter 2.0.0 released
* Fri Feb   1 2013 - NTT OSS Center 1.0.1-1
- Fix some bugs
* Fri Nov  30 2012 - NTT OSS Center 1.0.0-1
- pg_stats_reporter 1.0.0 released
