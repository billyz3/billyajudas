#!/usr/bin/env bash
set -euo pipefail

ACCOUNT_HOME="/home2/hg96b387"
TARGET="${ACCOUNT_HOME}/billyajudas.is-local.org"
REPO="${ACCOUNT_HOME}/billyajudas-repo"
REMOTE="https://github.com/billyz3/billyajudas.git"

case "$TARGET" in
  "$ACCOUNT_HOME"/*) ;;
  *) echo "Destino inseguro: $TARGET" >&2; exit 1 ;;
esac

if [[ ! -d "$REPO/.git" ]]; then
  git clone --branch main --single-branch "$REMOTE" "$REPO"
fi

cd "$REPO"
git fetch origin main
git checkout main
git pull --ff-only origin main

find . -name '*.php' -not -path './.git/*' -print0 | xargs -0 -n1 php -l >/dev/null
python3 tools/validate_site.py

mkdir -p "$TARGET" "$TARGET/storage/orders" "$TARGET/storage/logs"
rsync -a --delete \
  --exclude='.git/' \
  --exclude='.github/' \
  --exclude='config.local.php' \
  --exclude='storage/orders/' \
  --exclude='storage/logs/' \
  --exclude='tools/' \
  --exclude='*.md' \
  ./ "$TARGET/"

chmod 750 "$TARGET/storage" "$TARGET/storage/orders" "$TARGET/storage/logs" 2>/dev/null || true
echo "Deploy concluído em $TARGET"
echo "Execute depois: python3 $REPO/tools/smoke_production.py https://billyajudas.is-local.org"
