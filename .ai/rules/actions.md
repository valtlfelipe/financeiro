---
paths:
  - '{Dockerfile,docker/entrypoint.sh,config/financeiro.php,app/InstalledVersion.php,app/Actions/CheckForUpdates.php}'
---

# Actions

## Treat the image version as immutable deployment identity
Release builds write FINANCEIRO_VERSION to /app/VERSION, and runtime config reads that file before any inherited environment value. Clear Laravel deployment caches before rebuilding them, and scope update-check cache entries by the installed version so a new image cannot reuse the previous image's result.
