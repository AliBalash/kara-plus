#!/bin/sh
set -eu

CERT_DIR="/etc/nginx/certs"
CERT_FILE="$CERT_DIR/localhost.crt"
KEY_FILE="$CERT_DIR/localhost.key"
APP_ENV_FILE="/var/www/.env"

if [ -s "$CERT_FILE" ] && [ -s "$KEY_FILE" ]; then
    exit 0
fi

mkdir -p "$CERT_DIR"

cert_host="localhost"

if [ -f "$APP_ENV_FILE" ]; then
    env_app_url="$(awk -F= '/^APP_URL=/{print $2; exit}' "$APP_ENV_FILE" | tr -d '"' | tr -d "'")"

    if [ -n "$env_app_url" ]; then
        parsed_host="$(printf '%s' "$env_app_url" | sed -E 's#^[a-zA-Z]+://##; s#/.*$##; s/:.*$//')"

        if [ -n "$parsed_host" ]; then
            cert_host="$parsed_host"
        fi
    fi
fi

subject_alt_names="DNS:localhost,IP:127.0.0.1"

if [ "$cert_host" != "localhost" ]; then
    subject_alt_names="DNS:$cert_host,$subject_alt_names"
fi

openssl req \
    -x509 \
    -nodes \
    -newkey rsa:2048 \
    -days "${NGINX_CERT_DAYS:-3650}" \
    -keyout "$KEY_FILE" \
    -out "$CERT_FILE" \
    -subj "/CN=$cert_host" \
    -addext "subjectAltName=$subject_alt_names"

chmod 600 "$KEY_FILE"
chmod 644 "$CERT_FILE"
