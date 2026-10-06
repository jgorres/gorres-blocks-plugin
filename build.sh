#!/bin/bash
clear

# ==================================================
# Script name: build.sh
# Author: Jörn Gorres
# Date: 06.10.2026
# Description: Builds the release ZIP of the Gorres Blocks plugin for the
#              WordPress.org directory: checks the version numbers, compiles
#              every block in plugin/src/ with wp-scripts (including the
#              blocks manifest), exports plugin/ (including the block
#              sources in src/) without the files listed in .distignore
#              and zips the result.
# ==================================================

set -euo pipefail

echo "===================================================="
echo "   GORRES BLOCKS: RELEASE-ZIP ERZEUGEN"
echo "===================================================="
echo ""

REPO_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PLUGIN_DIR="${REPO_DIR}/plugin"
SLUG="gorres-blocks"
TARGET_DIR="${REPO_DIR}/dist"
STAGE_DIR="$(mktemp -d)"

trap 'rm -rf "${STAGE_DIR}"' EXIT

# Every folder in plugin/src/ with a block.json is one block.
BLOCKS=()
if [[ -d "${PLUGIN_DIR}/src" ]]; then
	while IFS= read -r block_json; do
		BLOCKS+=( "$(basename "$(dirname "${block_json}")")" )
	done < <(find "${PLUGIN_DIR}/src" -mindepth 2 -maxdepth 2 -name block.json | sort)
fi

if [[ "${#BLOCKS[@]}" -eq 0 ]]; then
	echo "Fehler: keine Blöcke in plugin/src/ gefunden." >&2
	exit 1
fi

echo "Blöcke: ${BLOCKS[*]}"

# --------------------------------------------------
# 1. Version numbers must agree everywhere.
# --------------------------------------------------
VERSION="$(sed -n 's/^ \* Version:[[:space:]]*\([0-9.]*\).*/\1/p' "${PLUGIN_DIR}/${SLUG}.php")"

if [[ -z "${VERSION}" ]]; then
	echo "Fehler: Version im Plugin-Header nicht gefunden." >&2
	exit 1
fi

echo "Version laut Plugin-Header: ${VERSION}"

check_version() {
	local label="$1"
	local found="$2"

	if [[ "${found}" != "${VERSION}" ]]; then
		echo "Fehler: ${label} steht auf '${found}', erwartet '${VERSION}'." >&2
		exit 1
	fi

	echo "  ok  ${label}"
}

check_version "Konstante JGOR_GBLK_VERSION" \
	"$(sed -n "s/^define( 'JGOR_GBLK_VERSION', '\([0-9.]*\)' );/\1/p" "${PLUGIN_DIR}/${SLUG}.php")"
check_version "readme.txt Stable tag" \
	"$(sed -n 's/^Stable tag:[[:space:]]*\([0-9.]*\).*/\1/p' "${PLUGIN_DIR}/readme.txt")"
check_version "package.json" \
	"$(sed -n 's/^[[:space:]]*"version":[[:space:]]*"\([0-9.]*\)".*/\1/p' "${REPO_DIR}/package.json")"
check_version "plugin/package.json" \
	"$(sed -n 's/^[[:space:]]*"version":[[:space:]]*"\([0-9.]*\)".*/\1/p' "${PLUGIN_DIR}/package.json")"

for block in "${BLOCKS[@]}"; do
	check_version "src/${block}/block.json" \
		"$(sed -n 's/^[[:space:]]*"version":[[:space:]]*"\([0-9.]*\)".*/\1/p' "${PLUGIN_DIR}/src/${block}/block.json")"
done

# --------------------------------------------------
# 2. Fresh block build.
# --------------------------------------------------
echo ""
echo "Blöcke bauen ..."

if [[ ! -d "${REPO_DIR}/node_modules" ]]; then
	echo "Fehler: node_modules fehlt, bitte zuerst 'npm install' ausführen." >&2
	exit 1
fi

rm -rf "${PLUGIN_DIR}/build"
( cd "${REPO_DIR}" && npm run build --silent )

for block in "${BLOCKS[@]}"; do
	if [[ ! -f "${PLUGIN_DIR}/build/${block}/block.json" ]]; then
		echo "Fehler: build/${block}/block.json fehlt nach dem Build." >&2
		exit 1
	fi
	echo "  ok  build/${block}"
done

if [[ ! -f "${PLUGIN_DIR}/build/blocks-manifest.php" ]]; then
	echo "Fehler: build/blocks-manifest.php fehlt nach dem Build." >&2
	exit 1
fi

echo "  ok  build/blocks-manifest.php"

# --------------------------------------------------
# 3. Export without the files from .distignore.
# --------------------------------------------------
echo ""
echo "Dateien zusammenstellen ..."

rsync -a --exclude-from="${REPO_DIR}/.distignore" "${PLUGIN_DIR}/" "${STAGE_DIR}/${SLUG}/"

if find "${STAGE_DIR}/${SLUG}" \( -name '*.po' -o -name '*.mo' -o -name '*.l10n.php' \) -print -quit | grep -q .; then
	echo "Fehler: Übersetzungsdateien im Paket gefunden." >&2
	exit 1
fi

if find "${STAGE_DIR}/${SLUG}" -name '.*' -print -quit | grep -q .; then
	echo "Fehler: versteckte Dateien im Paket gefunden." >&2
	exit 1
fi

# The WordPress.org guidelines require the human-readable sources of the
# compiled files in build/, so src/ must be part of the package.
for block in "${BLOCKS[@]}"; do
	if [[ ! -f "${STAGE_DIR}/${SLUG}/src/${block}/block.json" ]]; then
		echo "Fehler: src/${block}/ fehlt im Paket." >&2
		exit 1
	fi
	echo "  ok  src/${block}"
done

if [[ ! -f "${STAGE_DIR}/${SLUG}/package.json" ]]; then
	echo "Fehler: package.json fehlt im Paket." >&2
	exit 1
fi

echo "  ok  package.json"

FILE_COUNT="$(find "${STAGE_DIR}/${SLUG}" -type f | wc -l)"
echo "  ok  ${FILE_COUNT} Dateien"

# --------------------------------------------------
# 4. ZIP.
# --------------------------------------------------
echo ""
echo "ZIP schreiben ..."

mkdir -p "${TARGET_DIR}"
ZIP_FILE="${TARGET_DIR}/${SLUG}-${VERSION}.zip"
rm -f "${ZIP_FILE}"

( cd "${STAGE_DIR}" && zip -q -r -X "${ZIP_FILE}" "${SLUG}" )

echo "  ok  ${ZIP_FILE} ($(du -h "${ZIP_FILE}" | cut -f1))"
echo ""
echo "Inhalt:"
unzip -Z1 "${ZIP_FILE}" | grep -v '/$' | sed 's/^/  /'

echo ""
echo "===================================================="
echo "   Fertig!"
echo "===================================================="
