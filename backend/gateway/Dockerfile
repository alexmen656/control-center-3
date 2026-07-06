FROM node:22-bookworm-slim

WORKDIR /app
COPY package.json ./
RUN npm install --omit=dev
COPY server.js ./

USER node
EXPOSE 8790
CMD ["node", "server.js"]
