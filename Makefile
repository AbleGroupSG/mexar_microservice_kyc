# Makefile

# Docker image name and tag
DOCKER_IMAGE ?= mrsuner/mexar-msa-kyc
DOCKER_TAG ?= latest

# Environment files
# .env.docker: Docker Compose variables (DB_ROOT_PASSWORD, PUBLIC_DOMAIN, etc.)
# .env / .env.prod: Laravel app config (loaded by containers via env_file directive)
ENV_DOCKER_FILE = .env.docker

# ===================
# Docker Image
# ===================

# Build the Docker image (multi-platform)
build:
	docker build --platform linux/amd64,linux/arm64 -t $(DOCKER_IMAGE):$(DOCKER_TAG) -f docker/Dockerfile .

# Build for local platform only (faster)
build-local:
	docker build -t $(DOCKER_IMAGE):$(DOCKER_TAG) -f docker/Dockerfile .

# Push the Docker image to registry
push:
	docker push $(DOCKER_IMAGE):$(DOCKER_TAG)

# Build and push
release: build push

# ===================
# Local Development
# ===================

# Start local development environment
up:
	docker compose --env-file $(ENV_DOCKER_FILE) up -d

# Start with build
up-build:
	docker compose --env-file $(ENV_DOCKER_FILE) up -d --build

# Stop local development environment
down:
	docker compose --env-file $(ENV_DOCKER_FILE) down

# Restart all services
restart:
	docker compose --env-file $(ENV_DOCKER_FILE) restart

# ===================
# Production
# ===================

# Start production environment
prod-up:
	docker compose -f docker-compose.prod.yml --env-file $(ENV_DOCKER_FILE) up -d

# Stop production environment
prod-down:
	docker compose -f docker-compose.prod.yml --env-file $(ENV_DOCKER_FILE) down

# Restart production services
prod-restart:
	docker compose -f docker-compose.prod.yml --env-file $(ENV_DOCKER_FILE) restart

# Pull latest images for production
prod-pull:
	docker compose -f docker-compose.prod.yml --env-file $(ENV_DOCKER_FILE) pull

# Update production (pull + restart)
prod-update: prod-pull
	docker compose -f docker-compose.prod.yml --env-file $(ENV_DOCKER_FILE) up -d

# ===================
# Monitoring
# ===================

# View service status
ps:
	docker compose ps

# View all container logs
logs:
	docker compose logs -f

# View specific service logs
logs-app:
	docker compose logs -f app

logs-queue:
	docker compose logs -f queue

logs-scheduler:
	docker compose logs -f scheduler

logs-caddy:
	docker compose logs -f caddy

# ===================
# Laravel Commands
# ===================

# Run artisan command (usage: make artisan cmd="migrate")
artisan:
	docker compose exec app php artisan $(cmd)

# Run migrations
migrate:
	docker compose exec app php artisan migrate

# Run migrations (production)
prod-migrate:
	docker compose -f docker-compose.prod.yml exec app php artisan migrate --force

# Clear all caches
cache-clear:
	docker compose exec app php artisan cache:clear
	docker compose exec app php artisan config:clear
	docker compose exec app php artisan route:clear
	docker compose exec app php artisan view:clear

# Generate API documentation
docs:
	docker compose exec app php artisan scribe:generate

# Create new API user
create-user:
	docker compose exec app php artisan mexar:create-user

# ===================
# Database
# ===================

# Access MySQL CLI
mysql:
	docker compose exec mysql mysql -u root -p

# Backup database
db-backup:
	docker compose exec mysql mysqldump -u root -p mexar_kyc_msa > backup-$$(date +%Y%m%d-%H%M%S).sql

# ===================
# Cleanup
# ===================

# Stop and remove containers
clean:
	docker compose down

# Stop and remove containers + volumes (WARNING: deletes database data)
clean-all:
	docker compose down -v

# Remove unused Docker resources
prune:
	docker system prune -f

# ===================
# Setup Helpers
# ===================

# Initialize local development (first time setup)
init:
	@echo "Setting up local development environment..."
	@test -f .env || cp .env.example .env
	@test -f .env.docker || cp .env.docker.example .env.docker
	@echo "Please edit .env and .env.docker with your configuration"
	@echo "Then run: make up"

# Generate APP_KEY
key-generate:
	docker compose exec app php artisan key:generate

# ===================
# Help
# ===================

help:
	@echo "Available targets:"
	@echo ""
	@echo "  Docker Image:"
	@echo "    build        - Build multi-platform Docker image"
	@echo "    build-local  - Build for local platform only"
	@echo "    push         - Push image to registry"
	@echo "    release      - Build and push"
	@echo ""
	@echo "  Local Development:"
	@echo "    up           - Start local environment"
	@echo "    up-build     - Start with rebuild"
	@echo "    down         - Stop local environment"
	@echo "    restart      - Restart all services"
	@echo ""
	@echo "  Production:"
	@echo "    prod-up      - Start production environment"
	@echo "    prod-down    - Stop production environment"
	@echo "    prod-pull    - Pull latest images"
	@echo "    prod-update  - Pull and restart"
	@echo ""
	@echo "  Monitoring:"
	@echo "    ps           - View service status"
	@echo "    logs         - View all logs"
	@echo "    logs-app     - View app logs"
	@echo "    logs-queue   - View queue logs"
	@echo "    logs-scheduler - View scheduler logs"
	@echo ""
	@echo "  Laravel:"
	@echo "    artisan cmd=\"...\" - Run artisan command"
	@echo "    migrate      - Run migrations"
	@echo "    cache-clear  - Clear all caches"
	@echo "    create-user  - Create API user"
	@echo ""
	@echo "  Setup:"
	@echo "    init         - Initialize local development"
	@echo "    key-generate - Generate APP_KEY"
	@echo ""
	@echo "  Cleanup:"
	@echo "    clean        - Remove containers"
	@echo "    clean-all    - Remove containers + volumes"

.PHONY: build build-local push release up up-build down restart \
        prod-up prod-down prod-restart prod-pull prod-update \
        ps logs logs-app logs-queue logs-scheduler logs-caddy \
        artisan migrate prod-migrate cache-clear docs create-user \
        mysql db-backup clean clean-all prune init key-generate help
