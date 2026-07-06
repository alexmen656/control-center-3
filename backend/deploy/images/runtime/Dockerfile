FROM node:22-bookworm-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates \
    && rm -rf /var/lib/apt/lists/*

RUN useradd -u 10002 -m runner && mkdir -p /app && chown -R 10002:10002 /app

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER 10002
WORKDIR /app
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
