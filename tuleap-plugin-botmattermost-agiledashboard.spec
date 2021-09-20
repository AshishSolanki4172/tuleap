%define _prefix /usr
%define _datadir /usr/share
%define _bindir /usr/bin
%define _unitdir /usr/lib/systemd/system
%define _tmpfilesdir /usr/lib/tmpfiles.d
%define _buildhost tuleap-builder
%define _source_payload w9.xzdio
%define _binary_payload w9.xzdio

Name:		tuleap-plugin-botmattermost-agiledashboard
Version:	@@VERSION@@
Release:	@@RELEASE@@%{?dist}
BuildArch:	noarch
Summary:	Bot Mattermost AgileDashboard - Stand up summary

Group:		Development/Tools
License:	GPLv2
URL:		https://enalean.com
Source0:	%{name}-%{version}.tar.gz

BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Requires:	tuleap-plugin-agiledashboard, tuleap-plugin-botmattermost

%description
Bot Mattermost AgileDashboard - Stand up summary

%prep
%setup -q


%build

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/src/www/assets
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/botmattermost_agiledashboard
%{__cp} -ar db include site-content templates README.mkd VERSION .use-front-controller $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/botmattermost_agiledashboard
%{__cp} -ar assets $RPM_BUILD_ROOT/%{_datadir}/tuleap/src/www/assets/botmattermost_agiledashboard

%pre

%clean
%{__rm} -rf $RPM_BUILD_ROOT


%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/botmattermost_agiledashboard
%{_datadir}/tuleap/src/www/assets/botmattermost_agiledashboard


%changelog
* Tue Dec 20 2016 Humbert MOREAUX <humbert.moreaux@enalean.com> -
- First package
