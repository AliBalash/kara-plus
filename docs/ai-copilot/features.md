# Features and boundaries

## Available MVP

* **Contract Brief and Pulse** — a deterministic readiness score, verified missing-document, overdue-return and pending-payment facts, then an optional AI explanation. Its compact Contract 360 context includes safe lifecycle timing, non-identifying vehicle state, document presence, payment aggregates, amendment summaries and repeat-rental count; it excludes customer identity, contact fields and note text.
* **Operations Brief** — compact dashboard facts for overdue returns, pending-payment exposure and upcoming pickups.
* **Payment Queue Brief** — grouped pending-payment age batches; it preserves the distinction between pending transactions and operational balance.
* **Changes Since Last Login** — aggregated audit-event groups from the previous successful login, never raw audit entries.

Every feature is gated independently. The cards are lazy-loaded, render a skeleton while loading, show evidence links for accepted fact IDs, mark cached results, and remain non-blocking when Ajil is unavailable.

Cards also provide an optional Useful / Not useful signal. It records only the viewer, feature, non-authoritative insight reference and scope metadata; it never updates a rental, payment, customer, vehicle or any other operational record.

## Explicit non-goals

No AI path can alter contracts, customers, payments, cars, lead assignments, documents or approvals. There is no browser-to-Ajil endpoint, generic database chat, text-to-SQL, RAG, automated communications, or provider credentials in frontend state.
