#!/bin/bash

if [ ! -d "$TULEAP_PATH" ]; then
    echo "*** ERROR: TULEAP_PATH is missing"
    exit 1
fi

if [[ ! -d "$WORKSPACE" ]]; then
    echo "*** ERROR: WORKSPACE is missing"
    exit 1
fi

if [[ -z "$OS" ]]; then
    echo "*** ERROR: OS is missing"
    exit 1
fi

DOCKERIMAGE=build-plugin-botmattermost-rpm-"$OS"

RELEASE=1
LAST_TAG=$(git describe --abbrev=0 --tags)
NB_COMMITS=$(git log --oneline $LAST_TAG..HEAD | wc -l)
if [ $NB_COMMITS -gt 0 ]; then
    RELEASE=$((NB_COMMITS + 1))
fi

DIST='el6'
if [[ "$OS" = 'centos:7' ]]; then
    DIST='el7'
fi

docker build -t $DOCKERIMAGE -f "$TULEAP_PATH"/tools/utils/nix/build-tools.dockerfile "$TULEAP_PATH"/tools/utils/nix/
docker run --rm -v "$TULEAP_PATH":/tuleap:ro -v $PWD:/plugin:ro -v "$WORKSPACE":/output -w /plugin --tmpfs /build:rw,exec,nosuid --tmpfs /tmp --user "$(id -u):$(id -g)" -e RELEASE="$RELEASE" -e DIST="$DIST" $DOCKERIMAGE make docker-run
