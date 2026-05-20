FROM mcr.microsoft.com/playwright:v1.44.0-jammy

WORKDIR /app

COPY package.json ./
RUN npm install --omit=dev

# Playwright browsers already installed in base image
COPY src/ ./src/

ENV PORT=3100
ENV HEADLESS=true
ENV MAX_LISTINGS=60
ENV SCROLL_DELAY_MS=1200

EXPOSE 3100

CMD ["node", "src/index.js"]
