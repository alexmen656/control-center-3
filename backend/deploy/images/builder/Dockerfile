FROM node:22-bookworm-slim

RUN apt-get update \
    && apt-get install -y --no-install-recommends git python3 make g++ ca-certificates \
    && rm -rf /var/lib/apt/lists/*

RUN corepack enable || true

RUN useradd -u 10001 -m builder \
    && mkdir -p /build /out /src \
    && chown -R 10001:10001 /build /out

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER 10001
WORKDIR /build
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
