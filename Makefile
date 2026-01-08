# Makefile

# docker image name and tag
DOCKER_IMAGE=mrsuner/mexar-msa-kyc
DOCKER_TAG=latest

# set environment files
ENV_FILE=.env
ENV_FILE_PROD=.env.prod

# build the Docker image
build:
	docker build --platform linux/amd64,linux/arm64 -t $(DOCKER_IMAGE):$(DOCKER_TAG) -f docker/Dockerfile .

# push the Docker image to the registry
push:
	docker push $(DOCKER_IMAGE):$(DOCKER_TAG)

# set up local development environment
up:
	docker compose --env-file $(ENV_FILE) up -d

# shut down local development environment
down:
	docker compose down

# start production environment (without automatically loading override.yml)
prod-up:
	docker compose -f docker-compose.yml -f docker-compose.prod.yml --env-file $(ENV_FILE_PROD) up -d

# stop production environment
prod-down:
	docker compose -f docker-compose.yml -f docker-compose.prod.yml --env-file $(ENV_FILE_PROD) down

# view service status
ps:
	docker compose ps

# view container logs
logs:
	docker compose logs -f

# remove all mounted volumes (warning: will delete database data, etc.)
clean:
	docker compose down -v
