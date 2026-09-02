---
paths:
  - '.github/workflows/**'
---

# Workflows

## Releases are tag-driven to GHCR
Pushing a v*.*.* tag runs .github/workflows/release.yml: it builds linux/amd64 and linux/arm64, pushes ghcr.io/<owner>/<repo>, and creates the GitHub Release. Do not add a second publish path. Cut releases from a green main after moving CHANGELOG [Unreleased] entries into the version heading. Compose consumes FINANCEIRO_IMAGE (default ghcr.io/valtlfelipe/financeiro:latest).
