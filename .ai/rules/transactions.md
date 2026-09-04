---
paths:
  - 'app/Actions/Transactions/**'
---

# Transactions

## Carry opening balances through monthly cash positions
Treat initial_balance_minor as the account balance at the start of balance_date, so settled movements on that date count. A transaction is realized when settled_at is present, but its effective financial date is always due_on; editing due_on therefore moves the realized movement, and making it pending clears it from realized balances. Forecasts at or after today start from the current realized balance, include overdue pending movements and every movement after today through the target date exactly once. Past positions include only realized movements through their target date. Monthly forecast closing balances must carry forward as the next month's opening balance, keep integer minor-unit arithmetic, and net transfers across the workspace.

## Preserve installment totals
Installment series store the full purchase amount while each transaction stores its allocated installment amount. Non-monetary edits must preserve the original remainder distribution. When the amount of this and future installments changes, recompute the series total from its active occurrences instead of copying one installment amount into the series total.

## Archive accounts only after financial closure
Keep at least one active account. An account may be archived only at zero current balance and without pending entries, future movements, or active recurrences. Archived accounts remain part of historical summaries and filters, but new entries cannot target them. Existing history may change only descriptive fields so an archived balance cannot become hidden and nonzero.

## Serialize money as decimal integer strings
Database rows keep integer minor units. Aggregates use arbitrary-precision signed decimal arithmetic in PHP, and every monetary value sent to JavaScript is a decimal integer string. The frontend must format and compare those strings with BigInt and must never coerce API money to Number. Form inputs may submit safe integer minor units within their explicit digit limit.

## Enforce financial shapes in the database
Keep positive transaction and series amounts, known enum values, valid transfer source/destination shapes, paired installment metadata, valid recurrence shapes, nonzero installment allocations, and ordered series dates protected by database constraints. PostgreSQL uses CHECK constraints; SQLite test databases use equivalent INSERT and UPDATE triggers.
