#!/bin/bash

# Ensure we are in the battleui directory
# (The script is expected to be in /workspace/battleui/)

echo "Starting 3D UI Visual Smoke Test..."

# Run the specific Playwright test
npx playwright test tests/playwright/visual_smoke_test.spec.ts --project=chromium

if [ $? -eq 0 ]; then
    echo "Test completed successfully."
    echo "Logs saved to: js_log.log"
    echo "DOM saved to: dom.html"
else
    echo "Test failed or encountered errors."
    # Even if it fails, the test should have written what it could
    [ -f js_log.log ] && echo "Logs (partial) saved to: js_log.log"
    [ -f dom.html ] && echo "DOM (partial) saved to: dom.html"
    exit 1
fi
