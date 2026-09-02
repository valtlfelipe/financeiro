---
paths:
  - Dockerfile
---

# Dockerfile

## Docker frontend needs Wayfinder helper
resources/js/actions, routes, and wayfinder are gitignored. The vendor stage must run php artisan wayfinder:generate --with-form, and the frontend stage must COPY all three directories from vendor before npm run build. Copying only actions/routes leaves imports of ./wayfinder unresolved.
