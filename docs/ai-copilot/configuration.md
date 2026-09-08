# Configuration

Copy the documented variables from `.env.example`. Keep `KARA_AI_ENABLED=false` until Ajil is healthy. Set `AJIL_BASE_URL=http://ajil:8080` when using Docker Compose.

Create `ajil/.env` from `ajil/.env.example`; it is ignored by Git. Put only Ajil's `UAG_GEMINI_API_KEYS`, `UAG_GROQ_API_KEYS`, `UAG_AUTH_TOKEN`, and `UAG_ADMIN_TOKEN` there. Do not put provider keys in Laravel's `.env`.

Run the isolated sidecar with `docker compose -f docker-compose.local.yml -f docker-compose.ai.yml up -d --build`. The override injects only the Ajil client token and feature flags into Laravel; provider keys stay in Ajil. Ajil has no published host port and shares the application network only with Laravel and Redis.

`KARA_AI_ROUTING_STRATEGY=fallback_chain` is the production default: Ajil tries the next provider/key only when needed. Set `parallel_race` only after measuring a latency benefit and accepting that it intentionally starts concurrent provider attempts; Ajil selects the first valid response and cancels the race where supported.
