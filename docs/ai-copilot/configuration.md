# Configuration

Copy the documented variables from `.env.example`. Keep `KARA_AI_ENABLED=false` until Ajil is healthy. Set `AJIL_BASE_URL=http://ajil:8080` when using Docker Compose.

Create `ajil/.env` from `ajil/.env.example`; it is ignored by Git. Put only Ajil's `UAG_GEMINI_API_KEYS`, `UAG_GROQ_API_KEYS`, `UAG_AUTH_TOKEN`, and `UAG_ADMIN_TOKEN` there. Do not put provider keys in Laravel's `.env`.

Run the isolated sidecar with `docker compose -f docker-compose.local.yml -f docker-compose.ai.yml up -d --build`. The override injects only the Ajil client token and feature flags into Laravel; provider keys stay in Ajil. Ajil has no published host port and shares the application network only with Laravel and Redis.

The parent image wrapper resolves Ajil's embedded provider modules during its build, because the upstream Ajil release references one historical nested-submodule revision that GitHub no longer serves. This keeps the top-level Ajil submodule pinned to its public upstream commit and makes a fresh Kara Plus clone buildable.

`KARA_AI_ROUTING_STRATEGY=fallback_chain` is the production default: Ajil tries the next provider/key only when needed. Set `parallel_race` only after measuring a latency benefit and accepting that it intentionally starts concurrent provider attempts; Ajil selects the first valid response and cancels the race where supported.

The default chain starts with `gemini-3.5-flash-lite`, followed by `gemini-3.8-flash`, `openai/gpt-oss-20b`, then `qwen/qwen3.8-27b`. In the local September 29 run, 3.8 Flash waited roughly 12 seconds before timing out while 3.5 Flash-Lite completed in about 2.3 seconds, so the responsive model is tried first. Ajil accepts model IDs supplied in the request and performs key rotation, cooldown and provider fallback; its static default model is not used by these Laravel calls. The Ajil sidecar's own default is set to `gemini-3.5-flash-lite` for generic callers through `UAG_GEMINI_DEFAULT_MODEL`. Verify that the configured keys and live catalog support these candidates with `ai:health` before enabling the feature. The model defaults are overridable through `.env`.

Google applies Gemini API rate limits **per project**, not per API key. Rotating keys within one project does not create more project quota. Cache reuse and compact facts reduce requests; a 429 that exhausts the configured chain yields the verified local checks without a model-generated explanation. Google's free tier currently marks requests as usable to improve its products. Review this with the data owner before enabling free-tier use for CRM data; the current payload excludes names, phone numbers, emails, license/passport numbers, free-text notes and vehicle plates, but still includes operational IDs and aggregates. Free-tier terms and limits can change; see the official Gemini pricing and rate-limit pages.

Run `php artisan ai:health` from the Laravel container after deployment. It makes no chat request; it checks Ajil health and its compact, cached model-catalog summary. `KARA_AI_CATALOG_TIMEOUT` defaults to 35 seconds only for this operational check, so a cold upstream catalog does not falsely report a healthy sidecar as unavailable.

## Expert workspaces

The website review queue now has a queue brief using indexed counts for open, unassigned and older requests, plus at most five oldest request IDs with pickup timing and direct record links. A saved website request gets its own review brief using the same deterministic diagnostics as the approval workflow; the approval service still repeats the checks inside its transaction. A changed review vehicle or changed stored quote is surfaced explicitly. No customer name, phone, free-text note or plate is sent to Ajil.

The contract payment page has a separate ledger explanation. Laravel computes the operational balance with `Contract::calculateRemainingBalance()` and sends bounded payment-type aggregates, pending approval counts and transfer totals. That legacy balance calculation includes pending entries, so the assistant must not present it as confirmed debt or collected cash. The fleet list now has a seven-day outlook with bounded overdue-return links, future pickups/returns and separate past versus upcoming recorded service/insurance dates. No vehicle is recommended as available without a fresh availability check.

These four features are individually configurable through `KARA_AI_RESERVATION_TRIAGE_ENABLED`, `KARA_AI_RESERVATION_QUEUE_ENABLED`, `KARA_AI_CONTRACT_FINANCE_ENABLED` and `KARA_AI_FLEET_OUTLOOK_ENABLED`. They are visible to expert users and blocked for the driver role. Each card displays a small verified-facts list alongside the model wording, and still shows those facts when Ajil is unavailable.
