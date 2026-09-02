---
paths:
  - resources/js/lib/money-input.ts
---

# Lib

## Money inputs are cents-first
Amount fields treat typed digits as integer cents and always show two decimals with pt-BR grouping (1 → 0,01 → 12.345,00). Do not format left-to-right as a whole-reais integer with an optional comma. Parse by stripping non-digits, not by splitting on ',' and multiplying the integer part by 100.
