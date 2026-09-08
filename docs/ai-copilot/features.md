# Features and boundaries

## Available MVP

* **Contract Brief and Pulse** — a deterministic readiness score, verified missing-document, overdue-return, vehicle-availability, amendment and pending-payment facts, then an optional AI explanation. Its compact Contract 360 context includes safe lifecycle timing, six recent status transitions, three previous contracts, a non-identifying customer activity profile, non-identifying vehicle state, document presence, payment aggregates and breakdowns, and amendment summaries; it excludes customer identity, contact fields and note text.
* **Operations Brief** — compact dashboard facts for overdue returns, pending-payment exposure and upcoming pickups.
* **Payment Queue Brief** — grouped pending-payment age batches; it preserves the distinction between pending transactions and operational balance.
* **Changes Since Last Login** — aggregated audit-event groups from the previous successful login, never raw audit entries.

Every feature is gated independently. The cards are lazy-loaded, render a skeleton while loading, show evidence links for accepted fact IDs, mark cached results, and remain non-blocking when Ajil is unavailable. The page-aware right rail advertises only the insights supported by the current route; Contract 360 also appears at the bottom of the contract Edit workspace.

## Freshness and cache behavior

The default cache lifetime is `KARA_AI_CACHE_TTL=600` seconds. **Check latest data** always rebuilds the deterministic facts and compact context. If any relevant saved field changes, the input hash changes and Ajil receives a fresh request immediately; otherwise the still-valid saved insight is reused. **New analysis** explicitly bypasses that valid insight cache to request fresh wording from Ajil while preserving the same safety validation and in-flight lock. Unsaved browser form fields are never part of the context, so users must save a contract before checking its latest data.

Cards also provide an optional Useful / Not useful signal. It records only the viewer, feature, non-authoritative insight reference and scope metadata; it never updates a rental, payment, customer, vehicle or any other operational record.

## Explicit non-goals

No AI path can alter contracts, customers, payments, cars, lead assignments, documents or approvals. There is no browser-to-Ajil endpoint, generic database chat, text-to-SQL, RAG, automated communications, or provider credentials in frontend state.
