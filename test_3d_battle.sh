#!/bin/bash

# Ensure we are in the battleui directory
# (The script is expected to be in /workspace/battleui/)

echo "Starting 3D Battle Arena Visual & Logic Debug..."

# Run the specific Playwright test
npx playwright test tests/playwright/battle_debug.spec.ts --project=chromium

if [ $? -eq 0 ]; then
    echo "--------------------------------------------------"
    echo "DEBUG SESSION COMPLETED SUCCESSFULLY."
    echo "Logs saved to: battle_js_log.log"
    echo "DOM saved to: battle_dom.html"
    echo "--------------------------------------------------"
else
    echo "--------------------------------------------------"
    echo "DEBUG SESSION DETECTED ISSUES (As expected)."
    # Even if it fails, the test should have written what it could
    [ -f battle_js_log.log ] && echo "Logs (captured) saved to: battle_js_log.log"
    [ -f battle_dom.html ] && echo "DOM (captured) saved to: battle_dom.html"
    echo "--------------------------------------------------"
fi

# Also show the status of the main arena test
echo "Summary of Battle Arena Tests:"
npx playwright test tests/playwright/battle_arena.spec.ts --project=chromium --reporter=line
