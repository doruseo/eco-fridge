#!/usr/bin/env bash
# HSTS hardening on the live eco-fridge.co.uk (2026-11-22), safe to run again (every step checks first):
#   1. includeSubDomains in ecofridge-blocks/includes/security.php
#   2. the same HSTS header on the HTTPS redirect in wp-config.php (apex https://eco-fridge.co.uk -> www)
# Backups first, PHP syntax check, automatic restore on error, LiteSpeed purge.
# Hostinger: no awk, and wp-config.php cannot be replaced by rename (sed -i: "Device or resource busy"),
# so the edits are made with PHP, which rewrites the files in place.
# Run from Git Bash:  bash "/c/Users/doruh/Downloads/eco-fridge noul site/tools/hsts-harden.sh"
set -e
ssh ef-live 'bash -s' <<'REMOTE'
set -e
cd /home/u510186744/websites/PNxOJBHKQ/public_html
rm -f ./.hsts-*          # empty temp file left by the first version of this script
TS=20261122-hsts
S=wp-content/plugins/ecofridge-blocks/includes/security.php
[ -f "wp-config.php.bak-$TS" ] || cp -p wp-config.php "wp-config.php.bak-$TS"
[ -f "$S.bak-$TS" ] || cp -p "$S" "$S.bak-$TS"
php <<'PHPEOF'
<?php
// 1. plugin header
$p = 'wp-content/plugins/ecofridge-blocks/includes/security.php';
$s = file_get_contents( $p );
if ( strpos( $s, 'max-age=31536000; includeSubDomains' ) === false ) {
	$s = str_replace( "header( 'Strict-Transport-Security: max-age=31536000' );",
		"header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' );", $s, $n );
	if ( $n === 1 ) { file_put_contents( $p, $s ); echo "security.php updated\n"; } else { echo "security.php: HSTS line not found, left as it was\n"; }
} else { echo "security.php already has includeSubDomains\n"; }
// 2. HSTS on the apex redirect in wp-config.php
$f = 'wp-config.php';
$s = file_get_contents( $f );
if ( strpos( $s, 'HSTS on the redirect' ) !== false ) { echo "wp-config.php already has HSTS on the redirect\n"; exit( 0 ); }
$needle = "header( 'Location: https://www.eco-fridge.co.uk' . \$ef_canon_uri, true, 301 );";
$pos = strpos( $s, $needle );
if ( $pos === false ) { echo "wp-config.php: redirect line not found, left as it was\n"; exit( 0 ); }
$ls = strrpos( substr( $s, 0, $pos ), "\n" ) + 1;
$indent = substr( $s, $ls, $pos - $ls );
$line = $indent . "if ( \$ef_canon_https ) { header( 'Strict-Transport-Security: max-age=31536000; includeSubDomains' ); } // HSTS on the redirect too (apex)\n";
file_put_contents( $f, substr( $s, 0, $ls ) . $line . substr( $s, $ls ) );
echo "wp-config.php updated\n";
PHPEOF
if php -l wp-config.php >/dev/null && php -l "$S" >/dev/null; then
  echo "PHP syntax OK"
else
  echo "SYNTAX ERROR - restoring the backups"
  cat "wp-config.php.bak-$TS" > wp-config.php
  cat "$S.bak-$TS" > "$S"
  exit 1
fi
grep -n "Strict-Transport" wp-config.php "$S"
wp litespeed-purge all 2>/dev/null || echo "(purge LiteSpeed from wp-admin if WP-CLI cannot)"
REMOTE
echo
echo "--- check from outside"
for u in https://eco-fridge.co.uk/ https://www.eco-fridge.co.uk/; do
  echo "$u"; curl -s -o /dev/null -D - "$u" | grep -iE "^HTTP|strict-transport"
done
