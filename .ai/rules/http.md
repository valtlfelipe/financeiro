---
paths:
  - 'app/Http/**'
---

# Http

## Workspace invitation and membership boundaries
Invitation email checks are scoped to the current workspace and case-insensitive: reject existing members and unaccepted, unexpired invitations, but allow registered accounts from other workspaces. Only owners may cancel invitations or remove members; owners cannot be removed. Removal revokes pending invitations for the member in that workspace and preserves user accounts, financial history, and memberships in other workspaces.
