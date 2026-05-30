FROM node:20-slim

WORKDIR /app

COPY package.json ./
RUN npm install --omit=dev

# Install Playwright's own Chromium and all required system dependencies.
# Using --with-deps ensures the exact browser version Playwright expects is
# present together with every native library it needs (NSS, GBM, ATK, etc.).
# This is the recommended approach for containerised Playwright deployments
# and avoids the version-mismatch / missing-lib issues that occur when using
# the system chromium from apt with --no-install-recommends.
RUN npx playwright install --with-deps chromium

COPY src/ ./src/

ENV PORT=3100
ENV HEADLESS=true
ENV MAX_LISTINGS=60
ENV SCROLL_DELAY_MS=1200

EXPOSE 3100

CMD ["node", "src/index.js"]
