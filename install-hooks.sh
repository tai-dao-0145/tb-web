#!/bin/bash
echo "🔧 Installing Git hooks..."
HOOK_DIR=".git/hooks"
cp githooks/pre-commit $HOOK_DIR/pre-commit
chmod +x $HOOK_DIR/pre-commit
echo "✅ Git pre-commit hook installed!"
