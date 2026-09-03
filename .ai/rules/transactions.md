---
paths:
  - 'app/Actions/Transactions/**'
---

# Transactions

## Treat account balance date as end-of-day snapshot
An account's initial balance is the settled balance at the end of balance_date. Account balance calculations include only settled transactions whose due_on date is strictly later; same-day movements are already represented by the snapshot. Keep all arithmetic in integer minor units, and treat transfers as an equal debit from the source and credit to the destination.
