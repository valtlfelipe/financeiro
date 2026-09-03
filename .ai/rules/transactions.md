---
paths:
  - 'app/Actions/Transactions/**'
---

# Transactions

## Carry opening balances through monthly cash positions
Treat initial_balance_minor as the account balance at the start of balance_date, so settled movements on that date count. Actual balances follow settled_at; forecasts follow due_on. Monthly forecast closing balances must carry forward as the next month's opening balance, keep integer minor-unit arithmetic, and net transfers across the workspace.
