# Car Status Logic

## Goal

This document explains how car status now works after the `unavailable reason` refactor.

The system separates:

- manual operational intent set by staff
- final operational status calculated by the system

This keeps reservation-driven states automatic while preserving manual offline reasons.

## Stored Fields

The `cars` table now uses these fields together:

- `status`
  - final operational status used by the system
- `manual_status`
  - the manual base status selected by staff
- `manual_unavailability_reason`
  - the manual reason selected when `manual_status = unavailable`
- `availability`
  - compatibility flag derived from final operational status
- `unavailability_reason`
  - final current reason when the car is operationally unavailable

## Manual Statuses

Staff can manually set only:

- `available`
- `unavailable`
- `sold`

When `manual_status = unavailable`, staff can choose one of these reasons:

- `maintenance`
- `service_oil`
- `ac_problem`
- `accident`
- `insurance`
- `management_decision`
- `for_sale`
- `registration`

`need_action` is never selected manually.

## Final Operational Statuses

The final status shown in the UI can be:

- `available`
- `pre_reserved`
- `reserved`
- `unavailable`
- `sold`

Labels:

- `available` => `Available`
- `pre_reserved` => `Upcoming booking`
- `reserved` => `Active booking`
- `unavailable` => `Unavailable`
- `sold` => `Sold`

## Priority Order

The system calculates final status in this exact order:

1. `sold`
2. `need_action`
3. `reserved`
4. manual `unavailable`
5. `pre_reserved`
6. `available`

That means:

- an overdue open contract is stronger than an upcoming booking
- an active contract is stronger than a manual unavailable reason for display
- a manual unavailable reason is stronger than an upcoming booking

## Automatic Changes

### 1. Sold

If `manual_status = sold`:

- final `status = sold`
- `availability = false`
- `unavailability_reason = null`

Reservations do not override sold.

### 2. Need Action

If the car has an open reserving contract where:

- `pickup_date <= now`
- `return_date < now`
- and the contract status is still one of the reserving/open statuses

then the car becomes:

- `status = unavailable`
- `availability = false`
- `unavailability_reason = need_action`

This is automatic.

### 3. Active Booking

If `need_action` is not true, and the car has an active reservation window:

- `pickup_date <= now`
- and `return_date is null` or `return_date >= now`

then the car becomes:

- `status = reserved`
- `availability = false`
- `unavailability_reason = null`

### 4. Manual Unavailable

If the car is manually set to unavailable and it is not currently forced into `sold`, `need_action`, or `reserved`, then:

- `status = unavailable`
- `availability = false`
- `unavailability_reason = manual_unavailability_reason`

### 5. Upcoming Booking

If none of the higher-priority cases apply, and the car has a future reservation:

- `pickup_date > now`

then the car becomes:

- `status = pre_reserved`
- `availability = true`
- `unavailability_reason = null`

### 6. Available

If none of the above cases apply:

- `status = available`
- `availability = true`
- `unavailability_reason = null`

## Special Case: Need Action With Upcoming Booking

If a car is overdue on the current contract and also has another future booking:

- final status remains `Unavailable`
- final reason remains `Need Action`

The system does **not** downgrade it to `Upcoming booking`.

To make this visible to users, the UI now shows an extra note:

- `Upcoming booking also exists.`

This helps operations understand that:

- the current file is overdue and must be handled first
- there is also a future reservation waiting behind it

## Reservation Selection Logic

A car is blocked from reservation selection if:

- final status is `sold`
- final status is `unavailable`
- or manual status is not `available`

This means a manually unavailable car cannot be selected even if it has no date conflict.

`reserved` and `pre_reserved` are still system-managed operational states.

## Scheduler / Sync

Status sync runs in these situations:

- when a contract is created
- when a contract is updated
- when a contract is deleted
- when a car is edited
- every minute by scheduler via `cars:sync-operational-status`

The scheduled sync is important for time-based transitions like:

- active booking -> need action
- upcoming booking -> active booking
- stale pre-reserved -> available

## Reserving Contract Statuses

The car logic treats these contract statuses as reservation-driving:

- `pending`
- `assigned`
- `under_review`
- `reserved`
- `delivery`
- `inspection`
- `agreement_inspection`
- `awaiting_return`

## Legacy Handling

Older `under_maintenance` records are migrated into:

- `status = unavailable`
- `manual_status = unavailable`
- `manual_unavailability_reason = maintenance`
- `unavailability_reason = maintenance`

The code still understands legacy `under_maintenance` values for compatibility during transition.
