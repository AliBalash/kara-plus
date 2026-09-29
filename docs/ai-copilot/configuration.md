# Configuration

Copy the documented variables from `.env.example`. Keep `KARA_AI_ENABLED=false` until Ajil is healthy. Set `AJIL_BASE_URL=http://ajil:8080` when using Docker Compose.

Create `ajil/.env` from `ajil/.env.example`; it is ignored by Git. Put only Ajil's `UAG_GEMINI_API_KEYS`, `UAG_GROQ_API_KEYS`, `UAG_AUTH_TOKEN`, and `UAG_ADMIN_TOKEN` there. Do not put provider keys in Laravel's `.env`.

Run the isolated sidecar with `docker compose -f docker-compose.local.yml -f docker-compose.ai.yml up -d --build`. The override injects only the Ajil client token and feature flags into Laravel; provider keys stay in Ajil. Ajil has no published host port and shares the application network only with Laravel and Redis.

The parent image wrapper resolves Ajil's embedded provider modules during its build, because the upstream Ajil release references one historical nested-submodule revision that GitHub no longer serves. This keeps the top-level Ajil submodule pinned to its public upstream commit and makes a fresh Kara Plus clone buildable.

`KARA_AI_ROUTING_STRATEGY=fallback_chain` is the production default: Ajil tries the next provider/key only when needed. Set `parallel_race` only after measuring a latency benefit and accepting that it intentionally starts concurrent provider attempts; Ajil selects the first valid response and cancels the race where supported.

The default chain is `gemini-3.8-flash`, `gemini-3.5-flash-lite`, `openai/gpt-oss-20b`, then `qwen/qwen3.6-27b`. Ajil accepts model IDs supplied in the request and performs key rotation, cooldown and provider fallback; its static default model is not used by these Laravel calls. The Ajil sidecar's own default is set to `gemini-3.8-flash` for generic callers through `UAG_GEMINI_DEFAULT_MODEL`. Verify that the configured keys and live catalog support these candidates with `ai:health` before enabling the feature. The model defaults are overridable through `.env`.

Google applies Gemini API rate limits **per project**, not per API key. Rotating keys within one project does not create more project quota. Cache reuse and compact facts reduce requests; a 429 that exhausts the configured chain yields the verified local checks without a model-generated explanation. Google's free tier currently marks requests as usable to improve its products. Review this with the data owner before enabling free-tier use for CRM data; the current payload excludes names, phone numbers, emails, license/passport numbers, free-text notes and vehicle plates, but still includes operational IDs and aggregates. Free-tier terms and limits can change; see the official Gemini pricing and rate-limit pages.

Run `php artisan ai:health` from the Laravel container after deployment. It makes no chat request; it checks Ajil health and its compact, cached model-catalog summary. `KARA_AI_CATALOG_TIMEOUT` defaults to 35 seconds only for this operational check, so a cold upstream catalog does not falsely report a healthy sidecar as unavailable.
