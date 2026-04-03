#!/bin/bash
# T13.3 - Security tests execution script
cd /home/aur-lien/.picoclaw/workspace/vinyles-stock

echo "=== T13.3 Security Test Execution ==="
echo ""

# Check if skips exist in SecurityTest.php
SKIP_COUNT=$(grep -c "markTestSkipped" tests/Feature/Security/SecurityTest.php || echo "0")

if [ "$SKIP_COUNT" -gt 0 ]; then
    echo "⚠️  Found $SKIP_COUNT skipped tests - removing..."
    sed -i '/markTestSkipped/d' tests/Feature/Security/SecurityTest.php
    echo "✅ Skips removed"
else
    echo "✅ No skips found - all tests active"
fi

echo ""
echo "=== Running Security Tests ==="
php artisan test tests/Feature/Security/SecurityTest.php --no-interaction

EXIT_CODE=$?

echo ""
echo "=== Summary ==="
if [ $EXIT_CODE -eq 0 ]; then
    echo "✅ All security tests PASSED"
    exit 0
else
    echo "❌ Some tests FAILED"
    exit 1
fi
