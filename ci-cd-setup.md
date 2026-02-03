# CI/CD Setup Guide

## GitHub Actions Workflows

### 1. Dev Deployment (`deploy-dev.yml`)

- **Trigger**: Push to `dev` branch
- **Action**: SSH to server and run `deploy-kyc-dev.sh`

### 2. Staging Deployment (`deploy-staging.yml`)

- **Trigger**: Push to `staging` branch
- **Action**:
  1. Build multi-platform Docker image (`linux/amd64`, `linux/arm64`)
  2. Push image to Docker Hub as `mrsuner/mexar-msa-kyc:latest`
  3. SSH to staging server and run `deploy-kyc-staging.sh`

## Required GitHub Secrets

Go to **Repository Settings > Secrets and variables > Actions** to configure:

### Docker Hub

| Secret | Description |
|---|---|
| `DOCKERHUB_USERNAME` | Docker Hub account username |
| `DOCKERHUB_TOKEN` | Docker Hub access token (generate at Docker Hub > Account Settings > Security > New Access Token) |

### SSH Deployment

| Secret | Description |
|---|---|
| `SSH_PRIVATE_KEY` | SSH private key for connecting to deployment server(s) |
| `SSH_USER` | SSH username on the deployment server |
| `SSH_HOST` | IP address or hostname of the deployment server |

> **Note:** If dev and staging use different servers, create separate secrets (e.g. `SSH_HOST_STAGING`) and update the workflow accordingly.

## Server-Side Requirements

Each deployment server needs a corresponding deploy script in the user's home directory:

- **Dev server**: `deploy-kyc-dev.sh`
- **Staging server**: `deploy-kyc-staging.sh` — should pull the latest Docker image and restart services, e.g.:

```bash
#!/bin/sh
docker pull mrsuner/mexar-msa-kyc:latest
cd /path/to/staging && docker compose -f docker-compose.prod.yml up -d
```

## Docker Image

- **Registry**: Docker Hub
- **Image**: `mrsuner/mexar-msa-kyc`
- **Tag**: `latest` (staging build)
- **Dockerfile**: `docker/Dockerfile`
- **Build cache**: GitHub Actions cache (`type=gha`) for faster subsequent builds
