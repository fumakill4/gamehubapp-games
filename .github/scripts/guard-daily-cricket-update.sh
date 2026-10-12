#!/usr/bin/env bash
set -euo pipefail

echo "Checking GameHub Games automation safety boundaries..."

changed_files="$(
  {
    git diff --name-only
    git diff --cached --name-only
    git ls-files --others --exclude-standard
  } | sort -u
)"

blocked=0
if [ -n "$changed_files" ]; then
  while IFS= read -r path; do
    [ -z "$path" ] && continue
    case "$path" in
      index.php|guide-data.php|sitemap.xml|assets/img/thumb_*)
        ;;
      *)
        echo "::error file=$path::GameHub Games automation may not change this path."
        blocked=1
        ;;
    esac
  done <<EOF
$changed_files
EOF
fi

required_home_markers=(
  "require __DIR__ . '/guide-data.php'"
  "GameHub Games | Cricket Culture & Trending Game Guides for India"
  "id=\"GameHubGamesRichResultSchema\""
  "isset(\$_GET['ywag_admin'])"
  "'/private_bot'"
  "AW-18157597075"
)
for marker in "${required_home_markers[@]}"; do
  if ! grep -Fq "$marker" index.php; then
    echo "::error file=index.php::Missing protected homepage marker: $marker"
    blocked=1
  fi
done

for protected_file in guide.php guides.php .htaccess assets/css/styles.css assets/js/site.js; do
  if ! git diff --quiet -- "$protected_file" || ! git diff --cached --quiet -- "$protected_file"; then
    echo "::error file=$protected_file::Protected renderer/config file changed."
    blocked=1
  fi
done

for php_file in index.php guide-data.php guide.php guides.php; do
  php -l "$php_file"
done
xmllint --noout sitemap.xml

php -r '
require "guide-data.php";
$required = ["type", "title", "meta_title", "meta_description", "excerpt", "thumb", "intro", "sections", "faq", "related"];
if (!is_array($guides) || !$guides) { fwrite(STDERR, "No guides found\n"); exit(1); }
foreach ($guides as $slug => $guide) {
  if (!preg_match("/^[a-z0-9-]+$/", (string)$slug)) { fwrite(STDERR, "Invalid slug: $slug\n"); exit(1); }
  foreach ($required as $key) {
    if (!array_key_exists($key, $guide)) { fwrite(STDERR, "Missing $key in $slug\n"); exit(1); }
  }
  foreach ($guide["related"] as $related) {
    if (!isset($guides[$related])) { fwrite(STDERR, "Missing related slug $related in $slug\n"); exit(1); }
  }
  $thumb = "assets/img/thumb_" . $guide["thumb"] . ".webp";
  if (!is_file($thumb)) { fwrite(STDERR, "Missing thumbnail $thumb for $slug\n"); exit(1); }
}
foreach ([$hero_cards, $category_cards, $article_cards] as $cards) {
  foreach ($cards as $card) {
    if (!isset($guides[$card["slug"]])) { fwrite(STDERR, "Card references missing slug " . $card["slug"] . "\n"); exit(1); }
  }
}
echo "guide registry ok\n";
'

home_render="$(mktemp)"
library_render="$(mktemp)"
guide_render="$(mktemp)"
trap 'rm -f "$home_render" "$library_render" "$guide_render"' EXIT
php -d display_errors=1 -r '$_SERVER["REQUEST_URI"]="/"; include "index.php";' > "$home_render"
php -d display_errors=1 -r '$_SERVER["REQUEST_URI"]="/guides/"; include "index.php";' > "$library_render"
php -d display_errors=1 -r '$_SERVER["REQUEST_URI"]="/guides/cricket-games-india/"; include "index.php";' > "$guide_render"

if ! grep -Fq '<title>GameHub Games | Cricket Culture & Trending Game Guides for India</title>' "$home_render"; then
  echo "::error file=index.php::Homepage smoke render lost the protected title."
  blocked=1
fi
if ! grep -Fq 'Game Guides & Cricket Buzz' "$library_render" || ! grep -Fq 'Cricket Games in India' "$guide_render"; then
  echo "::error::Guide library or representative guide smoke render failed."
  blocked=1
fi
if grep -Eqi 'fatal error|warning:|parse error' "$home_render" "$library_render" "$guide_render"; then
  echo "::error::PHP smoke render emitted an error or warning."
  blocked=1
fi

if [ "$blocked" -ne 0 ]; then
  echo "GameHub Games safety guard failed. No commit will be pushed."
  exit 1
fi

echo "GameHub Games safety guard passed."
