FROM node:20-slim

# Install only Chromium (skip Firefox and WebKit bundled in playwright base)
RUN apt-get update && apt-get install -y \
    chromium \
    --no-install-recommends \
    && rm -rf /var/lib/apt/lists/*

# Tell Playwright to skip downloading browsers and use the system Chromium
ENV PLAYWRIGHT_SKIP_BROWSER_DOWNLOAD=1
ENV PLAYWRIGHT_CHROMIUM_EXECUTABLE_PATH=/usr/bin/chromium

WORKDIR /app

COPY package.json ./
RUN npm install --omit=dev

COPY src/ ./src/

ENV PORT=3100
ENV HEADLESS=true
ENV MAX_LISTINGS=60
ENV SCROLL_DELAY_MS=1200

EXPOSE 3100

CMD ["node", "src/index.js"]
