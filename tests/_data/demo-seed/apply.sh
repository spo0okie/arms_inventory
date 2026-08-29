#!/usr/bin/env bash
# Перезаливает канонический дамп в тестовую БД и накатывает демо-сиды по порядку.
#
#   tests/_data/demo-seed/apply.sh            # дамп + все сиды
#   tests/_data/demo-seed/apply.sh --seeds    # только сиды (дамп не трогаем)
#
# Переменные: MYSQL (клиент), DB_HOST, DB_PORT, DB_USER, DB_PASS, DB_NAME.
set -eu

dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
dump="$dir/../arms_demo.sql"

MYSQL="${MYSQL:-mysql}"
DB_HOST="${DB_HOST:-127.0.0.1}"
DB_PORT="${DB_PORT:-3306}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-arms_test}"

args=(-h "$DB_HOST" -P "$DB_PORT" -u "$DB_USER")
[ -n "$DB_PASS" ] && args+=(-p"$DB_PASS")

if [ "${1:-}" != "--seeds" ]; then
	echo "== заливаю $dump"
	"$MYSQL" "${args[@]}" < "$dump"
fi

for sql in "$dir"/[0-9][0-9]-*.sql; do
	[ -e "$sql" ] || continue
	echo "== $(basename "$sql")"
	"$MYSQL" "${args[@]}" "$DB_NAME" < "$sql"
done

echo "== готово"
