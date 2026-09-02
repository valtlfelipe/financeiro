---
paths:
  - compose.yaml
---

# General

## Self-host stack is image plus env vars
compose.yaml is the Portainer/Dockhand stack: published image only, no build, no env_file. Laravel reads process environment variables; do not require a .env inside the container. APP_KEY and DB_PASSWORD are mandatory and interpolated by Compose. Local image builds use compose.build.yaml as an overlay. The app entrypoint runs migrate --force; the scheduler must not.
