# Configuration

Copy the documented variables from `.env.example`. Keep `KARA_AI_ENABLED=false` until Ajil is healthy. Set `AJIL_BASE_URL=http://ajil:8080` when using Docker Compose.

Create `ajil/.env` from `ajil/.env.example`; it is ignored by Git. Put only Ajil's `UAG_GEMINI_API_KEYS`, `UAG_GROQ_API_KEYS`, `UAG_AUTH_TOKEN`, and `UAG_ADMIN_TOKEN` there. Do not put provider keys in Laravel's `.env`.

Run the isolated sidecar with `docker compose -f docker-compose.local.yml -f docker-compose.ai.yml up -d --build`. Ajil has no published host port and shares the application network only with Laravel and Redis.
