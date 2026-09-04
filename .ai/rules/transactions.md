---
paths:
  - 'app/Actions/Transactions/**'
---

# Transactions

## Carry opening balances through monthly cash positions
Treat initial_balance_minor as the account balance at the start of balance_date, so settled movements on that date count. A transaction is realized when settled_at is present, but its effective financial date is always due_on; editing due_on therefore moves the realized movement, and making it pending clears it from realized balances. Monthly forecast closing balances must carry forward as the next month's opening balance, keep integer minor-unit arithmetic, and net transfers across the workspace.
