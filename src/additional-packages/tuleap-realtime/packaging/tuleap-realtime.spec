%define _buildhost tuleap-builder
%define _source_payload w9.xzdio
%define _binary_payload w9.xzdio
%define debug_package %{nil}
%define __os_install_post %{nil}

%define target_path /usr/lib/tuleap-realtime

Name:       tuleap-realtime
Version:	  %{tuleap_version}
Release:	  1%{?dist}
Summary:    Tuleap realtime server

Group:      Development/Tools
License:    GPLv3
Source0:    %{name}
Source2:    %{name}.systemd-service

BuildArch:      x86_64

AutoReqProv: no
Requires(pre):   /usr/sbin/useradd
Requires: systemd

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root

%description
Tuleap realtime server

%build

%install
rm -rf               %{buildroot}
mkdir -p             %{buildroot}%{target_path}
cp -pr               %{SOURCE0} %{buildroot}%{target_path}/%{name}
install -p -D -m 644 %{SOURCE2} %{buildroot}%{_unitdir}/%{name}.service

%pre
getent group tuleaprt >/dev/null || groupadd -r tuleaprt
getent passwd tuleaprt >/dev/null || \
  useradd -r -g tuleaprt -s /sbin/nologin \
    -c "Tuleap realtime" tuleaprt
exit 0

%post
if [ $1 -eq 1 ]; then
    /usr/bin/systemctl enable %{name}.service &>/dev/null || :
fi

%preun
if [ $1 = 0 ]; then
    /usr/bin/systemctl stop %{name} &>/dev/null || :
    /usr/bin/systemctl disable %{name} &>/dev/null || :
fi

%posttrans
/usr/bin/systemctl condrestart %{name} &>/dev/null || :

%check

%clean
rm -rf %{buildroot}

%files
%defattr(-,root,root,-)
%attr(00644,root,root) %{_unitdir}/%{name}.service

%{target_path}/%{name}

