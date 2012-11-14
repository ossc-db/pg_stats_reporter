# rpm作成手順
# Subversionからexportしたファイル群をtarで固める
# % svn export https://.../pg_statsinfo/trunk/pg_stats_reporter
# % tar cvfz pg_stats_reporter_1.0.0.tar.gz pg_stats_reporter
# ファイルをSOURCESにコピーする
# % cp pg_stats_reporter_1.0.0.tar.gz pg_stats_reporter/rpm/SOURCES
# rpmを作成する
# % rpmbuild -ba pg_stats_reporter/rpm/SPECS/pg_stats_reporter.spec

Name:			pg_stats_reporter
Version:		1.0.0
Release:		1%{?dist}
Summary:		Graphical viewer for pg_statsinfo
Summary(ja):	pg_statsinfo 用グラフィカルビューア
Group:			Applications/Databases
License:		BSD
URL:			http://pgstatsinfo.projects.pgfoundry.org/index_ja.html
Packager:		NIPPON TELEGRAPH AND TELEPHONE CORPORATION
Source0:		pg_stats_reporter_1.0.0.tar.gz
BuildRoot:		%{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
BuildArch:		noarch
Requires:		php53 >= 5.3.3, php53-pgsql >= 5.3.3
Requires:		php53-common >= 5.3.3, php53-intl >= 5.3.3
Requires:		httpd >= 2.2
AutoReqProv: 0

%description

%prep
%setup -q -n %{name}

%install
/bin/mkdir -p %{buildroot}/var/www
/bin/cp -r html %{buildroot}/var/www
/bin/cp -r pg_stats_reporter %{buildroot}/var/www

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
/var/www/html/pg_stats_reporter/
/var/www/pg_stats_reporter/pg_stats_reporter.ini.sample
/var/www/pg_stats_reporter/lib/
/var/www/pg_stats_reporter/template/
/var/www/pg_stats_reporter/message/

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
