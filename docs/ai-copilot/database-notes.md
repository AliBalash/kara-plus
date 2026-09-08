# Read-only local schema inventory

The local Docker database was inspected read-only on 2026-09-08. It includes `contracts`, `payments`, `cars`, `customers`, `pickup_documents`, `return_documents`, `contract_statuses`, `contract_charges`, `contract_amendments`, `audit_events`, and related availability tables.

The dashboard fact engine relies on actual fields including `contracts.current_status`, `pickup_date`, `return_date`, `payments.approval_status`, `payments.amount_in_aed`, and `payments.payment_date`. Contract facts reuse `Contract::calculateRemainingBalance()` rather than reproducing financial rules. The large `contract_statuses` and `payments` tables are queried through compact aggregates; no raw table/collection is passed to Ajil.

The local snapshot has no lead records, so Lead Desk is intentionally deferred rather than producing speculative output.
