# Functional Requirements - ksf_FA_Rep_Audit

## FR-AUDIT-001-001: Bad GL Transaction Balances Report
**BABOK Related**: BR-AUDIT-001

Identifies GL transactions where the sum of amounts doesn't balance to zero.

### Parameters
- Date range
- Comments

### Output
PDF/Excel report with transaction details showing unbalanced entries.

---

## FR-AUDIT-001-002: Amount Mismatches Report
**BABOK Related**: BR-AUDIT-001

Identifies discrepancies between GL transaction totals and bank transaction amounts.

### Parameters
- Date From
- Date To
- Comments

### Output
PDF/Excel report showing GL vs Bank amount mismatches.

---

## FR-AUDIT-001-003: Contaminated Transactions Report
**BABOK Related**: BR-AUDIT-001

Identifies GL transactions that have line items with different dates (data corruption).

### Parameters
- Date From
- Date To
- Comments

### Output
PDF/Excel report showing transactions with multiple dates.

---

## FR-AUDIT-001-004: Orphaned Transactions Report
**BABOK Related**: BR-AUDIT-001

Identifies GL transactions that have no corresponding bank transaction.

### Parameters
- Date From
- Date To
- Comments

### Output
PDF/Excel report showing orphaned GL transactions.
