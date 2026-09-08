# Ajil is kept as a git submodule. Its current upstream release references a
# Gemini proxy submodule revision that is no longer hosted upstream; resolve
# only the missing provider modules during image construction so a fresh Kara
# Plus clone remains reproducible without modifying Ajil's public history.
FROM python:3.11-slim

WORKDIR /app
COPY ajil/unified_gateway/requirements.txt /app/unified_gateway/requirements.txt

RUN apt-get update \
    && apt-get install -y --no-install-recommends git ca-certificates \
    && rm -rf /var/lib/apt/lists/* \
    && printf 'precedence ::ffff:0:0/96  100\n' >> /etc/gai.conf \
    && pip install --no-cache-dir --retries 20 --upgrade pip setuptools wheel \
    && pip install --no-cache-dir --retries 20 -r /app/unified_gateway/requirements.txt

COPY ajil/ /app/

RUN set -eux; \
    if [ ! -f /app/modules/gemini_proxy/api/app/config.py ]; then rm -rf /app/modules/gemini_proxy && git clone https://github.com/M2002HR/Gemini_API_Proxy_Service.git /app/modules/gemini_proxy && git -C /app/modules/gemini_proxy checkout --detach caad33dcd0e88ac488f1a937cbc7aa729830be20; fi; \
    if [ ! -f /app/modules/groq_proxy/api/app/config.py ]; then rm -rf /app/modules/groq_proxy && git clone https://github.com/AliBalash/Groq_API_Proxy_Service.git /app/modules/groq_proxy && git -C /app/modules/groq_proxy checkout --detach 597a76134d32d28e0c2dd2911f80d071a4ce1a9e; fi; \
    if [ ! -f /app/modules/pollinations_proxy/api/app/config.py ]; then rm -rf /app/modules/pollinations_proxy && git clone https://github.com/AliBalash/Pollinations_API_Proxy_Service.git /app/modules/pollinations_proxy && git -C /app/modules/pollinations_proxy checkout --detach 483994e7471fd8fa1cfac555fe824c7d2117ae57; fi

EXPOSE 8080
CMD ["uvicorn", "unified_gateway.app.main:app", "--host", "0.0.0.0", "--port", "8080"]
