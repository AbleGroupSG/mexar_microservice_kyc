# Deployment Guide

This guide covers local development setup and production deployment for the KYC MSA Service.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Environment Files](#environment-files)
- [Local Development](#local-development)
- [Production Deployment](#production-deployment)
- [Building Docker Images](#building-docker-images)
- [Service Management](#service-management)
- [Troubleshooting](#troubleshooting)

---

## Prerequisites

- Docker Engine 24.0+
- Docker Compose v2.20+
- (For image push) Docker Hub account

---

## Environment Files

The project uses a layered environment file structure:

| File | Purpose | Git Status |
|------|---------|------------|
| `.env` | Laravel config (local) | Ignored |
| `.env.example` | Template for `.env` | Tracked |
| `.env.docker` | Docker Compose variables | Ignored |
| `.env.docker.example` | Template for `.env.docker` | Tracked |
| `.env.prod` | Laravel config (production) | Ignored |
| `.env.prod.example` | Template for `.env.prod` | Tracked |

**Separation of concerns:**
- **Laravel files** (`.env`, `.env.prod`): App config, API keys, credentials
- **Docker file** (`.env.docker`): Container/compose variables like `DB_ROOT_PASSWORD`, `PUBLIC_DOMAIN`, `DOCKER_IMAGE`

---

## Local Development

### 1. Setup Environment Files

```bash
# Copy templates
cp .env.example .env
cp .env.docker.example .env.docker
```

### 2. Configure Environment

Edit `.env`:
```bash
# Generate APP_KEY (run after containers are up)
# Or set manually: base64 encoded 32-char string
APP_KEY=

# Database password (must match .env.docker)
DB_PASSWORD=your-password

# Configure API integrations as needed
MEXAR_URL=http://localhost:8000
# ... other configs
```

Edit `.env.docker`:
```bash
# MySQL root password
DB_ROOT_PASSWORD=your-password

# Database name
DB_DATABASE=mexar_kyc_msa
```

### 3. Add Local Domain to Hosts

```bash
# Add to /etc/hosts
echo "127.0.0.1 kyc.local" | sudo tee -a /etc/hosts
```

### 4. Start Services

```bash
# Build and start all containers
docker compose up -d --build

# Generate APP_KEY if needed
docker compose exec app php artisan key:generate

# View logs
docker compose logs -f
```

### 5. Access Application

- **Application**: https://kyc.local (accept self-signed certificate)
- **Health Check**: https://kyc.local/healthz

### Services Overview

| Service | Description | Port |
|---------|-------------|------|
| app | PHP-FPM application server | 9000 (internal) |
| mysql | MySQL 8.4.7 database | 3306 (internal) |
| redis | Redis 7 cache/queue | 6379 (internal) |
| queue | Laravel queue worker | - |
| scheduler | Laravel task scheduler | - |
| caddy | Reverse proxy (HTTPS) | 80, 443 |

---

## Production Deployment

### 1. Setup Environment Files

```bash
# Copy templates
cp .env.prod.example .env.prod
cp .env.docker.example .env.docker
```

### 2. Configure Production Environment

Edit `.env.prod`:
```bash
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:your-production-key
APP_URL=https://your-domain.com

# Strong database password
DB_PASSWORD=strong-production-password

# Redis for queue (recommended for production)
QUEUE_CONNECTION=redis

# Production API credentials
MEXAR_URL=https://mexar-backend.example.com
MEXAR_KEY_SECRET=your-production-secret

# RegTank credentials
COMPANY_SPECIFIC_REGTANK_SERVICE_URL=https://api.regtank.com/company
REGTANK_CRM_SERVER_URL=https://api.regtank.com/crm
CLIENT_ID_TEMPLATE=your-client-id
CLIENT_SECRET_TEMPLATE=your-client-secret
REGTANK_ASIGNEE=your-assignee-id

# GlairAI credentials
GLAIR_OCR_BASE_URL=https://api.glair.ai
GLAIR_API_KEY=your-api-key
GLAIR_USERNAME=your-username
GLAIR_PASSWORD=your-password

# Disable dashboard in production
DASHBOARD_ENABLED=false
```

Edit `.env.docker`:
```bash
# Docker Hub image
DOCKER_IMAGE=your-dockerhub-username/mexar-kyc
IMAGE_TAG=v1.0.0

# Strong MySQL password
DB_ROOT_PASSWORD=strong-production-password
DB_DATABASE=mexar_kyc_msa

# Your production domain
PUBLIC_DOMAIN=your-domain.com

# Email for Let's Encrypt certificate notifications
WEBMASTER_EMAIL=admin@your-domain.com
```

### 3. Pull and Start Services

```bash
# Pull latest images
docker compose -f docker-compose.prod.yml --env-file .env.docker pull

# Start services
docker compose -f docker-compose.prod.yml --env-file .env.docker up -d

# View logs
docker compose -f docker-compose.prod.yml logs -f
```

### 4. Verify Deployment

```bash
# Check all services are running
docker compose -f docker-compose.prod.yml ps

# Check health endpoint
curl -k https://your-domain.com/healthz

# Check application logs
docker compose -f docker-compose.prod.yml logs app
```

### SSL/TLS Certificate

Caddy automatically obtains and renews Let's Encrypt certificates. Requirements:
- Port 80 and 443 must be accessible from the internet
- Domain DNS must point to the server
- `WEBMASTER_EMAIL` must be a valid email address

Certificates are persisted in the `caddy_data` volume.

---

## Building Docker Images

### Manual Build

```bash
# Build image
docker build -t your-dockerhub-username/mexar-kyc:v1.0.0 -f docker/Dockerfile .

# Tag as latest
docker tag your-dockerhub-username/mexar-kyc:v1.0.0 your-dockerhub-username/mexar-kyc:latest

# Push to Docker Hub
docker login
docker push your-dockerhub-username/mexar-kyc:v1.0.0
docker push your-dockerhub-username/mexar-kyc:latest
```

### CI/CD Build (GitHub Actions Example)

```yaml
# .github/workflows/docker-build.yml
name: Build and Push Docker Image

on:
  push:
    tags:
      - 'v*'

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - name: Login to Docker Hub
        uses: docker/login-action@v3
        with:
          username: ${{ secrets.DOCKERHUB_USERNAME }}
          password: ${{ secrets.DOCKERHUB_TOKEN }}

      - name: Build and push
        uses: docker/build-push-action@v5
        with:
          context: .
          file: ./docker/Dockerfile
          push: true
          tags: |
            ${{ secrets.DOCKERHUB_USERNAME }}/mexar-kyc:${{ github.ref_name }}
            ${{ secrets.DOCKERHUB_USERNAME }}/mexar-kyc:latest
```

---

## Service Management

### Common Commands

```bash
# Local development
docker compose up -d                    # Start all services
docker compose down                     # Stop all services
docker compose logs -f                  # View all logs
docker compose logs -f app              # View app logs
docker compose exec app bash            # Shell into app container
docker compose restart queue            # Restart queue worker

# Production
docker compose -f docker-compose.prod.yml --env-file .env.docker up -d
docker compose -f docker-compose.prod.yml --env-file .env.docker down
docker compose -f docker-compose.prod.yml --env-file .env.docker logs -f
```

### Laravel Artisan Commands

```bash
# Run migrations
docker compose exec app php artisan migrate

# Clear caches
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear

# Create API user
docker compose exec app php artisan mexar:create-user

# Generate JWT token
docker compose exec app php artisan mexar:generate-jwt-token

# Register webhooks with RegTank
docker compose exec app php artisan app:webhook:register --isEnabled=true
```

### Database Operations

```bash
# Access MySQL CLI
docker compose exec mysql mysql -u root -p

# Backup database
docker compose exec mysql mysqldump -u root -p mexar_kyc_msa > backup.sql

# Restore database
docker compose exec -T mysql mysql -u root -p mexar_kyc_msa < backup.sql
```

### Updating Production

```bash
# Pull new image
docker compose -f docker-compose.prod.yml --env-file .env.docker pull

# Restart with new image (zero-downtime for stateless services)
docker compose -f docker-compose.prod.yml --env-file .env.docker up -d

# Run migrations if needed
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

---

## Troubleshooting

### Container won't start

```bash
# Check container logs
docker compose logs app

# Check if ports are in use
lsof -i :80
lsof -i :443

# Rebuild without cache
docker compose build --no-cache
```

### Database connection refused

```bash
# Wait for MySQL to be ready (handled by entry-point.sh)
# Check MySQL logs
docker compose logs mysql

# Verify credentials match between .env and .env.docker
grep DB_PASSWORD .env
grep DB_ROOT_PASSWORD .env.docker
```

### Redis connection issues

```bash
# Check Redis is running
docker compose exec redis redis-cli ping
# Should return: PONG

# Check Redis logs
docker compose logs redis
```

### Let's Encrypt certificate issues

```bash
# Check Caddy logs for ACME errors
docker compose -f docker-compose.prod.yml logs caddy

# Verify domain DNS is pointing to server
dig your-domain.com

# Check port 80/443 are accessible
curl http://your-domain.com/.well-known/acme-challenge/test
```

### Queue jobs not processing

```bash
# Check queue worker logs
docker compose logs queue

# Restart queue worker
docker compose restart queue

# Check failed jobs
docker compose exec app php artisan queue:failed
```

### Scheduler not running

```bash
# Check scheduler logs
docker compose logs scheduler

# Manually run scheduler
docker compose exec scheduler php artisan schedule:run
```

---

## Architecture Diagram

```
                                    ┌─────────────────┐
                                    │   Internet      │
                                    └────────┬────────┘
                                             │
                                    ┌────────▼────────┐
                                    │  Caddy (443)    │
                                    │  HTTPS/TLS      │
                                    └────────┬────────┘
                                             │
                    ┌────────────────────────┼────────────────────────┐
                    │                        │                        │
           ┌────────▼────────┐     ┌─────────▼─────────┐    ┌─────────▼─────────┐
           │   App (FPM)     │     │   Queue Worker    │    │    Scheduler      │
           │   Port 9000     │     │   artisan queue   │    │   schedule:run    │
           └────────┬────────┘     └─────────┬─────────┘    └─────────┬─────────┘
                    │                        │                        │
                    └────────────────────────┼────────────────────────┘
                                             │
                    ┌────────────────────────┼────────────────────────┐
                    │                        │                        │
           ┌────────▼────────┐     ┌─────────▼─────────┐
           │     MySQL       │     │      Redis        │
           │   Port 3306     │     │    Port 6379      │
           └─────────────────┘     └───────────────────┘
```
