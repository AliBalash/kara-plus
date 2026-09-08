# Features and boundaries

## Available MVP

* **Contract Brief and Pulse** — a deterministic readiness score, verified missing-document, overdue-return and pending-payment facts, then an optional AI explanation.
* **Operations Brief** — compact dashboard facts for overdue returns, pending-payment exposure and upcoming pickups.
* **Payment Queue Brief** — grouped pending-payment age batches; it preserves the distinction between pending transactions and operational balance.
* **Changes Since Last Login** — aggregated audit-event groups from the previous successful login, never raw audit entries.

Every feature is gated independently. The cards are lazy-loaded, render a skeleton while loading, show evidence links for accepted fact IDs, mark cached results, and remain non-blocking when Ajil is unavailable.

## Explicit non-goals

No AI path can alter contracts, customers, payments, cars, lead assignments, documents or approvals. There is no browser-to-Ajil endpoint, generic database chat, text-to-SQL, RAG, automated communications, or provider credentials in frontend state.
