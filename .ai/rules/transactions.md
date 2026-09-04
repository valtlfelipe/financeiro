---
paths:
  - 'app/Actions/Transactions/**'
---

# Transactions

## Carry opening balances through monthly cash positions
Treat initial_balance_minor as the account balance at the start of balance_date, so settled movements on that date count. A transaction is realized when settled_at is present, and its cash date is the earlier of due_on and the workspace civil day it was settled on: settling an entry ahead of its due date moves the money right away, while settling one late keeps it attributed to due_on so ticking off a backlog never rewrites history. Editing due_on therefore moves the realized movement, and making it pending clears it from realized balances. Forecasts at or after today start from the current realized balance and add every still-pending movement due through the target date exactly once, so overdue pending entries are carried and settled entries are never counted twice. Past positions include only realized movements through their target date. Compare settled_at against an absolute instant bound in PHP (the start of the day after the target date in the workspace timezone) rather than converting timestamps to dates in SQL, which keeps the civil day correct on both PostgreSQL and SQLite. Monthly forecast closing balances must carry forward as the next month's opening balance, keep integer minor-unit arithmetic, and net transfers across the workspace.

## Preserve installment totals
Installment series store the full purchase amount while each transaction stores its allocated installment amount. Non-monetary edits must preserve the original remainder distribution. When the amount of this and future installments changes, recompute the series total from its active occurrences instead of copying one installment amount into the series total.

## Archive accounts only after financial closure
Keep at least one active account. An account may be archived only at zero current balance and without pending entries, future movements, or active recurrences. Archived accounts remain part of historical summaries and filters, but new entries cannot target them. Existing history may change only descriptive fields so an archived balance cannot become hidden and nonzero.

## Serialize money as decimal integer strings
Database rows keep integer minor units. Aggregates use arbitrary-precision signed decimal arithmetic in PHP, and every monetary value sent to JavaScript is a decimal integer string. The frontend must format and compare those strings with BigInt and must never coerce API money to Number. Form inputs may submit safe integer minor units within their explicit digit limit.

## Enforce financial shapes in the database
Keep positive transaction and series amounts, known enum values, valid transfer source/destination shapes, paired installment metadata, valid recurrence shapes, nonzero installment allocations, and ordered series dates protected by database constraints. PostgreSQL uses CHECK constraints; SQLite test databases use equivalent INSERT and UPDATE triggers.

## Do not let zero opening balances hide history
Treat a zero initial balance as a neutral baseline rather than a dated snapshot: recorded movements before balance_date must still affect realized and forecast positions. A non-zero initial balance remains a dated snapshot, so movements before balance_date stay excluded to prevent double counting.
