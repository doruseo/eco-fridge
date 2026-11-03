#!/bin/bash
# Run one of the F15 SQL scripts against a chosen source and target.
#
#   ./f15-run.sh --sql f15-compare.sql      --old 'oldsite.43lz2ypq8_' --new 'wordpress.wp_'
#   ./f15-run.sh --sql f15-import-orders.sql --old 'oldsite.43lz2ypq8_' --new 'wordpress.wp_' [--dry-run]
#
# --old / --new are "database.prefix". Both halves are required, and the two may
# name the same database with different prefixes, which is how this runs on
# staging: the clone's tables are loaded next to ours, imported, then dropped.
#
# --dry-run stops after the preflight counts, so nothing is written.
#
# The MariaDB client is reached either directly or, with --docker <container>,
# inside a container. Credentials come from the environment:
#   DB_USER (default root), MYSQL_PWD, DB_HOST (optional).

set -euo pipefail

HERE="$(cd "$(dirname "$0")" && pwd)"
SQL=""
SINCE="2026-07-10"
OLD=""
NEW=""
DRY=0
DOCKER=""

while [ $# -gt 0 ]; do
	case "$1" in
		--sql)    SQL="$HERE/$(basename "$2")"; shift 2 ;;
		--since)  SINCE="$2"; shift 2 ;;
		--old)    OLD="$2"; shift 2 ;;
		--new)    NEW="$2"; shift 2 ;;
		--docker) DOCKER="$2"; shift 2 ;;
		--dry-run) DRY=1; shift ;;
		*) echo "unknown argument: $1" >&2; exit 2 ;;
	esac
done

if [ -z "$OLD" ] || [ -z "$NEW" ] || [ -z "$SQL" ]; then
	echo "--sql, --old and --new are all required; --old/--new are database.prefix" >&2
	exit 2
fi
if [ ! -f "$SQL" ]; then
	echo "no such script: $SQL" >&2
	exit 2
fi

qualify() { # turn db.prefix into `db`.`prefix
	printf '`%s`.`%s' "${1%%.*}" "${1#*.}"
}

OLD_Q="$(qualify "$OLD")"
NEW_Q="$(qualify "$NEW")"
TARGET_DB="${NEW%%.*}"

# The SQL writes @NEW@posts, which becomes `db`.`wp_posts`: the opening
# backtick of the table name is carried by the placeholder, so the table name
# closes it.
render() {
	sed -e "s|@OLD@\([a-z_]*\)|$OLD_Q\1\`|g" \
	    -e "s|@NEW@\([a-z_]*\)|$NEW_Q\1\`|g" \
	    -e "s|@SINCE@|$SINCE|g" \
	    "$SQL"
}

run_sql() {
	if [ -n "$DOCKER" ]; then
		docker exec -i -e MYSQL_PWD "$DOCKER" mariadb -u"${DB_USER:-root}" -t --default-character-set=utf8mb4 "$TARGET_DB"
	else
		mariadb -u"${DB_USER:-root}" ${DB_HOST:+-h"$DB_HOST"} -t --default-character-set=utf8mb4 "$TARGET_DB"
	fi
}

if [ "$DRY" = "1" ]; then
	echo "# dry run: preflight only, nothing is written"
	render | sed -n '/^-- 0\./,/^-- 1\./p' | run_sql
	exit 0
fi

echo "# $(basename "$SQL"): $OLD -> $NEW   (since $SINCE)"
render | run_sql
