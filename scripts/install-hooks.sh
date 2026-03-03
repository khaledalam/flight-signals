#!/usr/bin/env bash
# Install git pre-commit hook for Pint formatting checks
set -e

HOOK_DIR="$(git rev-parse --show-toplevel)/.git/hooks"
HOOK_FILE="${HOOK_DIR}/pre-commit"

cat > "$HOOK_FILE" << 'EOF'
#!/usr/bin/env bash
set -e

echo "🔍 Running Pint (code style)..."
./vendor/bin/pint --test

echo "✅ Pre-commit checks passed."
EOF

chmod +x "$HOOK_FILE"
echo "✅ Pre-commit hook installed at ${HOOK_FILE}"
