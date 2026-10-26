# Fleet Price Management

The authenticated page is **Cars → Price Management**, `/expert/car/prices`.
Access is available to authenticated panel staff except users with the `driver`
role, including staff accounts without an assigned role. The same rule controls
the menu link and every preview, selection and apply action. Authorization reads
the current user again so assigning the driver role immediately revokes access.
The legacy `manage fleet prices` permission does not override the driver restriction.

## Stage A: regular tariffs

Filters only search inventory. Checkboxes and the two **Add** selection buttons
build an explicit ID set. Adding all matches is a snapshot, not a saved query.
Changing filters or adding a newly matching vehicle cannot expand that set.
Selections survive pagination. The selected-ID disclosure and preview show the
exact target set, including selections hidden by the current filters.

All nine rental/LDW/SCDW fields support exact AED, percentage increase/decrease,
and fixed AED addition/subtraction. Disabled fields are never assigned. A blank
legacy tariff stays NULL unless explicitly set; relative operations on NULL
require an exact value first. Money uses integer cents and half-up rounding,
within the existing DECIMAL(10,2) range. A 100.00 tariff increased by 12.50% is
112.50; 0.01 decreased by 50% rounds to 0.01. Empty values never mean zero.

Preview stores a server-side operation receipt but does **not change tariffs**.
After reviewing before/after values, staff must check confirmation and apply.
Previews expire after 30 minutes. Applying locks the receipt and exact car rows
in ascending ID order, checks relevant original values, and saves Eloquent
models in one transaction. Missing cars, changed prices or audit failures abort
the entire transaction. Repeating an applied operation returns its receipt and
cannot compound a percentage/addition. No rollback button rewrites later edits.

`fleet_price_operations` is a durable preview/idempotency ledger containing the
actor, exact IDs and original/proposed prices. This is deliberately separate from
the audit log's configurable (currently 30-day) retention. Per-model audit events
and a batch event with the operation ID are emitted through existing auditing.
The operation ledger is not automatically pruned: do not remove retry receipts
without a defined retention/idempotency policy.

The nine existing vehicle columns remain the regular tariff authority. Legacy
`price_per_day`, `ldw_price` and `scdw_price` readers were inspected and are not
rewritten or synchronized into a second tariff authority. Existing Edit Car,
contract balances, payments and amendment policies are preserved.

## Stage B: date events

Use the same ID selection and switch to **Date-Based Prices**. Specify an event
name and inclusive start/end dates in **Asia/Dubai**, enable rental tiers, and
select exact AED or percentage adjustments. New events must start today or later
in Dubai. Insurance remains on its regular tariff logic. Percentages apply to
the regular tariff at quote time; the preview shows their effect against today's
base values. Fixed prices do not change with later regular tariff edits.

`fleet_price_windows` stores each grouped event; `fleet_price_window_rates`
stores explicit `(event, car, tier, method, value)` associations. No brand/model/
year matching is used for writes. An event can be created inactive. Activation
and deactivation also require preview and confirmation. Overlaps are forbidden
for an active car/tier/date pair. Adjacent non-overlapping windows and different
tiers are allowed. Selected-car locks serialize event creation and activation
with base updates and reservation submission. Expired events cannot be activated.

Total rental duration still determines the 1–6, 7–27 or 28+ tier, using the existing
`RentalDuration::billableDays()` grace policy. Each charged day is anchored at
pickup plus N days; its Dubai calendar date chooses the event. For example,
November 1–4 (three billable days) at 100 regular with a 200 event on November 2–3
costs exactly 500 AED base rental. Grace does not invent an additional priced day.
For bounded public API work, reservation durations above 3,660 days are rejected.

## Public API and customer acknowledgement

Existing `pricing` and `insurance_pricing` keys are retained. `/cars` and
`/catalog-selection` add `date_pricing` when dates are supplied, containing the
duration tier, exact base total and display-only average daily rate. Full daily
schedules are returned by `/quote`, avoiding an oversized schedule on every card.
Grouping distinguishes individual vehicles with different date-effective prices.
React uses the date-effective average for sorting/cards, labels normal tariffs,
and displays the exact base total and expandable daily schedule in checkout.

`/quote` adds `rental_pricing` and a server-generated `quote_fingerprint`.
`/submit` recalculates while holding the selected car lock. A stale fingerprint
(or a missing fingerprint on a seasonal booking) returns HTTP 409:

```json
{"code":"price_changed","message":"…","data":{"quote":{"quote_fingerprint":"…","final_total":525}}}
```

Nothing is created on that response. React shows the previous/new totals and
requires explicit acknowledgement of the currently displayed fingerprint before
retrying with the same request UUID. A further change requires acknowledgement
again. Existing completed UUID retries return their original contract even if
current tariffs differ. Regular-price legacy clients can still omit the
fingerprint; deploy the updated React client to enable stale-price protection for
all public bookings. Client daily-rate/discount/total overrides are rejected.

The exact daily schedule and base total are stored in `quote_snapshot`,
`pricing_tariffs.rental_pricing` and the base charge's metadata. Existing submitted
or confirmed contracts are never visited by tariff/event updates. The legacy
`used_daily_rate` is **the captured regular tariff reference**, not a blended rate.
Existing extensions therefore keep their existing captured-rate/current-tariff
choices and policies; they do not retroactively apply new seasonal rules. The
snapshot records that reference explicitly. Staff performing deliberate commercial
edits still use the existing edit/amendment policies; this feature adds no repricer
for historical bookings.

## Deployment and checks

1. Back up and run the two additive migrations before enabling the backend code.
   Use the normal application deployment process; no production migration is run
   by this implementation. The permission is retained on migration rollback.
2. Deploy backend, then build/deploy the reservation React client.
3. Verify staff access and the driver restriction. Keep date events inactive until
   the new client is live; then preview and activate a small exact-vehicle event.
4. Smoke-test ordinary and deep-linked reservations, stale-quote acknowledgement,
   and a mixed regular/event booking on the target database.

Focused tests cover explicit selections, all nine fields, decimal/null handling,
conflict/rollback/idempotency, permission enforcement, audit events, duration/grace
boundaries, overlap races represented by competing previews, API/contract snapshots,
and existing Car/Edit/Amendment/review flows. Local tests use SQLite; real parallel
InnoDB locking should also be exercised in staging before production release.

## Task file manifest

Backend additions:

- `app/Livewire/Pages/Panel/Expert/Car/FleetPriceManagement.php`
- `resources/views/livewire/pages/panel/expert/car/fleet-price-management.blade.php`
- `app/Models/FleetPriceOperation.php`
- `app/Models/FleetPriceWindow.php`
- `app/Models/FleetPriceWindowRate.php`
- `app/Support/FleetPriceMath.php`
- `app/Services/Fleet/FleetBulkPricingService.php`
- `app/Services/Fleet/FleetDatePricingService.php`
- `app/Services/Fleet/SeasonalPricingService.php`
- `app/Services/Reservations/ReservationPriceChanged.php`
- `database/migrations/2026_10_18_100000_create_fleet_price_operations.php`
- `database/migrations/2026_10_18_110000_create_fleet_price_windows.php`
- `tests/Feature/Livewire/Car/FleetPriceManagementTest.php`
- `tests/Feature/Fleet/FleetDatePricingTest.php`
- `docs/fleet-price-management.md`

Backend integration edits:

- `routes/web.php`
- `resources/views/livewire/components/panel/aside.blade.php`
- `app/Services/Reservations/PublicReservationService.php`
- `app/Http/Requests/Api/ReservationQuoteRequest.php`

Reservation React integration edits:

- `src/App.jsx`
- `src/api.js`

Earlier uncommitted payment-classification changes and the deep-link step-skip
change are preserved; they are not part of the fleet-pricing file manifest.
