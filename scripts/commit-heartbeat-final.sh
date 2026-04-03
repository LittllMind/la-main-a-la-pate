#!/bin/bash
# Final heartbeat commit - T10/T11 cleanup

cd /home/aur-lien/.picoclaw/workspace/vinyles-stock

# Stage workflows git add .github/
git add .github/

# Stage documentation git add docs/T11-F-CI-CD.md
git add docs/T11-F-CI-CD.md

# Stage scripts git add scripts/
git add scripts/

# Stage tests git add tests/Feature/Orders/TestOrderStockMovementCommandTest.php

# Commit message : git commit -m "feat(T10-T11): Filtres alertes stock + 128 tests + CI/CD

- Filtres multicritères stock-alerts (6 filtres + export CSV)
- 128 tests feature/integration (Fonds, Vinyles, Mouvements, Commandes)
- GitHub Actions CI/CD (tests + deploy)
- Documentation workflows"

echo "✅ Commit final T10-T11 prêt"
