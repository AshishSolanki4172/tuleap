Name:		tuleap-plugin-mytuleap-contact-support
Version:	@@VERSION@@
Release:	@@RELEASE@@%{?dist}
BuildArch:	noarch
Summary:	myTuleap Contact support

Group:		Development/Tools
License:	GPLv2
URL:		https://enalean.com
Source0:	%{name}-%{version}.tar.gz

BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root
Requires:	tuleap >= 11.8


%description
%{summary}.

%prep
%setup -q


%build

%install
%{__rm} -rf $RPM_BUILD_ROOT

%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/src/www/assets
%{__install} -m 755 -d $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/mytuleap_contact_support
%{__cp} -ar vendor etc include site-content templates README.md VERSION www .use-front-controller $RPM_BUILD_ROOT/%{_datadir}/tuleap/plugins/mytuleap_contact_support
%{__cp} -ar assets $RPM_BUILD_ROOT/%{_datadir}/tuleap/src/www/assets/mytuleap_contact_support

%clean
%{__rm} -rf $RPM_BUILD_ROOT

%files
%defattr(-,root,root,-)
%{_datadir}/tuleap/plugins/mytuleap_contact_support
%{_datadir}/tuleap/src/www/assets/mytuleap_contact_support
