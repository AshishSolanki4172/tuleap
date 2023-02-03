#!/bin/bash

set -e

if [ ! -d "$TULEAP_PATH" ]; then
    echo "*** ERROR: TULEAP_PATH is missing"
    exit 1
fi

if [ ! -d "$WORKSPACE" ]; then
    echo "*** ERROR: WORKSPACE is missing"
    exit 1
fi

if [[ -z "$OS" ]]; then
    echo "*** ERROR: OS is missing"
    exit 1

fi

DOCKERIMAGE=build-plugin-mytuleap-contact-support-rpm-"$OS"

PACKAGE_VERSION="$(tr -d '[:space:]' < VERSION)"

RELEASE=1
LAST_TAG="$(git describe --abbrev=0 --tags 2> /dev/null | echo 'No tag yet')"
if [ "$LAST_TAG" == "$PACKAGE_VERSION" ]; then
    NB_COMMITS=$(git log --oneline "$LAST_TAG"..HEAD | wc -l)
    if [ "$NB_COMMITS" -gt 0 ]; then
	    RELEASE=$((NB_COMMITS + 1))
    fi
fi

docker build -t $DOCKERIMAGE -f "$TULEAP_PATH"/tools/utils/nix/build-tools.dockerfile "$TULEAP_PATH"/tools/utils/nix/
docker run --rm -v "$TULEAP_PATH":/tuleap:ro -v $PWD:/plugin:ro -v "$WORKSPACE":/output -w /plugin --tmpfs /build:rw,exec,nosuid --tmpfs /tmp --user "$(id -u):$(id -g)" -e RELEASE="$RELEASE" -e OS="$OS" "$DOCKERIMAGE" make docker-run
