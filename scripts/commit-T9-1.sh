#!/bin/bash
# Script de commit T9.1 - Fix routes + Style mouvements

cd $(dirname "$0")/..

git add routes/web.php resources/views/mouvements/index.blade.php
git add MARATHON.md HEARTBEAT.md

git commit -m "T9.1: fix doublon routes + style violet/rose mouvements stock"

echo "✅ Commit effectué !"
git log --oneline -3