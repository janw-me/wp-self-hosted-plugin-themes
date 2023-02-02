#!/usr/bin/env bash

# Directory to self https://stackoverflow.com/a/246128/933065
SELF_PATH="$( cd -- "$(dirname "$0")" >/dev/null 2>&1 ; pwd -P )"

if [[ -z "$WEBSITE_URL" ]]; then
  echo "Set the WEBSITE_URL secret"
  exit 1
fi
if [[ -z "$WEBSITE_USER" ]]; then
  echo "Set the WEBSITE_USER secret"
  exit 1
fi
if [[ -z "$WEBSITE_PASS" ]]; then
  echo "Set the WEBSITE_PASS secret"
  exit 1
fi

# Allow some ENV variables to be customized
if [[ -z "$SLUG" ]]; then
  SLUG=${GITHUB_REPOSITORY#*/}
fi
echo "SLUG is $SLUG"

if [[ -z "$BUILD_DIR" ]]; then
  BUILD_DIR=./
fi
BUILD_DIR=${BUILD_DIR%/} # Strip trailing slash, always.

echo "BUILD_DIR is $BUILD_DIR"

# Determine version
if [[ -f "$BUILD_DIR/style.css" ]]; then
  VERSION=$(grep  'Version:.*' "$BUILD_DIR/style.css" | sed -E "s/.* ([.0-9])/\\1/")
  echo "Theme version is ${VERSION}"
else
  VERSION=$(grep  'Version:.*' "$BUILD_DIR/$SLUG.php" | sed -E "s/.* ([.0-9])/\\1/")
  echo "Plugin version is ${VERSION}"
fi
if [[ -z "$VERSION" ]]; then
  echo "Cannot determine version. It should be in ${SLUG}.php for plugins of style.css for themes."
  exit 1
fi

# Check for readme file.
if [[ ! -f "$BUILD_DIR/readme.txt" ]]; then
  echo "Cannot find readme.txt file. It is required."
  exit 1
fi
README_FILE="$BUILD_DIR/readme.txt"

# Full path to the zip file.
ZIP_FILE="${BUILD_DIR}/${SLUG}.${VERSION}.zip"

# Build the list of excludes.

if [[ -f ${BUILD_DIR}/.distignore ]]; then
  ZIP_EXCLUDES=() # will hold the ignore files.
  while IFS= read -r IGNORE_FILE; do
    if [[ ${IGNORE_FILE} == \#* ]]; then
      continue # ignore lines with a comment.
    fi
    if [[ -d "${BUILD_DIR}/${IGNORE_FILE}" ]]; then
      ZIP_EXCLUDES+="${SLUG}/${IGNORE_FILE}/* " # directories
    fi
    # Match files and patterns.
    ZIP_EXCLUDES+="${SLUG}/${IGNORE_FILE} "
  done < ${BUILD_DIR}/.distignore

  ZIP_EXCLUDES="-x ${ZIP_EXCLUDES[@]}"
else
  ZIP_EXCLUDES=''
  echo "No .distignore file found. Including everything in the zip."
fi

# Go up one dir. Only way to proper to make sure ZIP uses the correct directory.
cd "${BUILD_DIR}/../"

# Finally zip it up.
set -o noglob #https://stackoverflow.com/a/11456496/933065
zip -r -q ${ZIP_FILE} ./$( basename ${BUILD_DIR}) ${ZIP_EXCLUDES}
set +o noglob # the `*` at the end of directories kept expanding.
echo "Created zip file in ${ZIP_FILE}"

$SELF_PATH/deploy.php "$WEBSITE_URL" "$WEBSITE_USER" "$WEBSITE_PASS"
