# MEXAR MSA KYC

A Laravel 11 microservice for KYC (Know Your Customer), KYB (Know Your Business), and OCR operations. This service provides standardized KYC screening capabilities across multiple providers with webhook notifications and status polling.

## Table of Contents

- [Features](#features)
- [Architecture](#architecture)
- [How It Works](#how-it-works)
- [API Workflow](#api-workflow)
- [Installation](#installation)
- [Configuration](#configuration)
- [API Reference](#api-reference)
- [Testing](#testing)
- [Development](#development)

## Features

- **Multi-Provider KYC Screening**: Support for RegTank and GlairAI providers
- **KYB (Know Your Business)**: Company screening and verification
- **OCR Processing**: Indonesian KTP and Passport document OCR via GlairAI
- **Async Workflow**: All screening operations follow async-first pattern
- **Multi-Tenant API Keys**: Each client can have multiple API keys with individual webhook URLs
- **Webhook Notifications**: Automatic notifications when screening completes
- **Status Polling**: RESTful API to check screening status
- **Comprehensive Testing**: Full test coverage for all workflows

## Architecture

### System Components

```
┌─────────────────────────────────────────────────────────────────┐
│                         Client Application                       │
│                    (MEXAR, E-Form, etc.)                        │
└────────────┬────────────────────────────────────┬───────────────┘
             │                                    │
             │ 1. POST /api/screen                │ 5. Webhook
             │    (with X-API-KEY)                │    Notification
             ▼                                    │
┌─────────────────────────────────────────────────┴───────────────┐
│                    KYC MSA (This Service)                        │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  API Layer (Controllers)                                  │  │
│  │  - KycScreenerController                                  │  │
│  │  - Status polling endpoint                                │  │
│  └────────────────────┬─────────────────────────────────────┘  │
│                       │                                          │
│  ┌────────────────────▼─────────────────────────────────────┐  │
│  │  Service Layer (Factory Pattern)                          │  │
│  │  - KYCServiceFactory                                       │  │
│  │    ├─ RegtankService                                       │  │
│  │    ├─ GlairAIService                                       │  │
│  │    └─ TestService                                          │  │
│  └────────────────────┬─────────────────────────────────────┘  │
│                       │                                          │
│  ┌────────────────────▼─────────────────────────────────────┐  │
│  │  Job Queue (Async Processing)                             │  │
│  │  - GlairAIVerificationJob                                  │  │
│  │  - Background webhook delivery                             │  │
│  └────────────────────┬─────────────────────────────────────┘  │
│                       │                                          │
│  ┌────────────────────▼─────────────────────────────────────┐  │
│  │  Data Layer                                                │  │
│  │  - users (clients)                                         │  │
│  │  - users_api_keys (API keys with webhook_url)            │  │
│  │  - kyc_profiles (screening records)                       │  │
│  │  - webhook_logs                                            │  │
│  │  - api_request_logs                                        │  │
│  └────────────────────────────────────────────────────────────┘  │
└───────────┬──────────────────────────────────────┬──────────────┘
            │                                      │
            │ 2. Call Provider API                 │ 4. Receive
            │                                      │    Result
            ▼                                      │
┌─────────────────────────────┐    ┌──────────────▼──────────────┐
│    RegTank API              │    │     GlairAI API             │
│    (via webhook)            │    │     (synchronous call)      │
└─────────────────────────────┘    └─────────────────────────────┘
            │
            │ 3. Webhook callback
            │    from RegTank
            ▼
┌─────────────────────────────────────────────────────────────────┐
│          Webhook Receiver (RegtankWebhookController)            │
└─────────────────────────────────────────────────────────────────┘
```

### Key Design Patterns

1. **Factory Pattern**: `KYCServiceFactory` creates appropriate service instances based on provider type
2. **Service Interface**: All KYC providers implement `KYCServiceInterface` for consistency
3. **Async-First**: All screening operations return immediately with a reference ID
4. **Queue-Based Processing**: Background jobs handle async verification and webhook delivery
5. **Multi-Tenant**: API keys are scoped to users with individual webhook configurations

### Database Schema

```
users
├── id
├── name
├── email
└── password

users_api_keys
├── id
├── user_id (FK → users)
├── name
├── api_key (unique)
├── signature_key (optional)
├── webhook_url (client's webhook endpoint)
└── deleted_at (soft delete)

kyc_profiles
├── id (UUID, primary key)
├── user_id (FK → users)
├── user_api_key_id (FK → users_api_keys)
├── provider (regtank, glair, test)
├── provider_reference_id
├── profile_data (JSON)
├── provider_response_data (JSON)
├── status (pending, approved, rejected, error, unresolved)
├── created_at
└── updated_at
```

## How It Works

### Async Workflow (All Providers)

All KYC screening operations follow a consistent async pattern:

1. **Client submits KYC request** → MSA creates profile with `PENDING` status
2. **MSA returns reference ID** → Client receives UUID immediately
3. **Background processing**:
   - RegTank: Waits for webhook from provider
   - GlairAI: Job dispatched to call API asynchronously
4. **Status update** → Profile updated to `APPROVED`, `REJECTED`, or `ERROR`
5. **Webhook notification** → MSA sends result to client's configured `webhook_url`
6. **Status polling** → Client can poll `/api/status/{uuid}` anytime

### Provider-Specific Details

#### RegTank Flow

```
POST /api/screen → [Create Profile: PENDING] → Call RegTank API
                                                      ↓
Client receives: { "identity": "uuid-123" }    Store reference_id
                                                      ↓
                                               ⏳ Wait for webhook
                                                      ↓
POST /webhooks/kyc ← RegTank sends result ← [Update Profile: APPROVED/REJECTED]
         ↓
[Send to client's webhook_url] → Client receives notification
```

#### GlairAI Flow

```
POST /api/screen → [Create Profile: PENDING] → Dispatch GlairAIVerificationJob
                            ↓
Client receives: { "identity": "uuid-456" }
                                                      ↓
                                               Job calls GlairAI API
                                                      ↓
                                        [Update Profile: APPROVED/REJECTED]
                                                      ↓
                                        [Send to client's webhook_url]
```

## API Workflow

### 1. Submit KYC Screening Request

**Endpoint:** `POST /api/screen`

**Headers:**
```
X-API-KEY: your-api-key-here
Content-Type: application/json
```

**Request Body (RegTank):**
```json
{
  "personal_info": {
    "first_name": "John",
    "last_name": "Doe",
    "date_of_birth": "1990-01-15",
    "nationality": "SG"
  },
  "identification": {
    "id_type": "passport",
    "id_number": "A1234567",
    "issuing_country": "SG",
    "issue_date": "2020-01-01",
    "expiry_date": "2030-01-01"
  },
  "address": {
    "city": "Singapore",
    "country": "SG",
    "address_line": "123 Orchard Road"
  },
  "contact": {
    "email": "john.doe@example.com",
    "phone": "+6512345678"
  },
  "meta": {
    "service_provider": "regtank",
    "reference_id": "CLIENT-REF-001"
  }
}
```

**Request Body (GlairAI):**
```json
{
  "personal_info": {
    "first_name": "Budi",
    "last_name": "Santoso",
    "date_of_birth": "1985-03-20",
    "nationality": "ID"
  },
  "identification": {
    "id_type": "national_id",
    "id_number": "3201012003850001",
    "issuing_country": "ID"
  },
  "address": {
    "city": "Jakarta",
    "country": "ID",
    "address_line": "Jl. Sudirman No. 10"
  },
  "meta": {
    "service_provider": "glair",
    "reference_id": "CLIENT-REF-002"
  }
}
```

**Response (Immediate):**
```json
{
  "meta": {
    "code": 200,
    "message": "Screening successful",
    "request_id": "req-uuid-789"
  },
  "data": {
    "identity": "550e8400-e29b-41d4-a716-446655440000"
  }
}
```

### 2. Poll Status (Optional)

**Endpoint:** `GET /api/status/{uuid}`

**Headers:**
```
X-API-KEY: your-api-key-here
```

**Response (Pending):**
```json
{
  "meta": {
    "code": 200,
    "message": "Status retrieved successfully",
    "request_id": "req-uuid-790"
  },
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "status": "pending",
    "provider": "regtank",
    "provider_reference_id": "REF-123456",
    "created_at": "2025-10-27T10:30:00.000000Z",
    "updated_at": "2025-10-27T10:30:00.000000Z"
  }
}
```

**Response (Completed):**
```json
{
  "meta": {
    "code": 200,
    "message": "Status retrieved successfully",
    "request_id": "req-uuid-791"
  },
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "status": "approved",
    "provider": "regtank",
    "provider_reference_id": "REF-123456",
    "created_at": "2025-10-27T10:30:00.000000Z",
    "updated_at": "2025-10-27T10:35:00.000000Z"
  }
}
```

### 3. Webhook Notification (Automatic)

When screening completes, the MSA sends a webhook to the `webhook_url` configured for your API key.

**Endpoint:** `POST {your-webhook-url}`

**Payload:**
```json
{
  "event": "kyc.status.changed",
  "payload": {
    "msa_reference_id": "550e8400-e29b-41d4-a716-446655440000",
    "provider_reference_id": "REF-123456",
    "reference_id": "CLIENT-REF-001",
    "platform": "regtank",
    "status": "approved",
    "verified": true,
    "verified_at": "2025-10-27T10:35:00.000000Z",
    "rejected_at": null,
    "message": "KYC verification completed risk level: LOW",
    "review_notes": "Approved",
    "failure_reason": null
  }
}
```

**Webhook for Rejection:**
```json
{
  "event": "kyc.status.changed",
  "payload": {
    "msa_reference_id": "550e8400-e29b-41d4-a716-446655440000",
    "provider_reference_id": "REF-123456",
    "reference_id": "CLIENT-REF-001",
    "platform": "glair",
    "status": "rejected",
    "verified": false,
    "verified_at": null,
    "rejected_at": "2025-10-27T10:35:00.000000Z",
    "message": "KYC verification completed",
    "review_notes": "GlairAI identity verification",
    "failure_reason": "Document mismatch"
  }
}
```

**Webhook for Error:**
```json
{
  "event": "kyc.status.changed",
  "payload": {
    "msa_reference_id": "550e8400-e29b-41d4-a716-446655440000",
    "provider_reference_id": null,
    "reference_id": "CLIENT-REF-001",
    "platform": "glair",
    "status": "error",
    "verified": false,
    "verified_at": null,
    "rejected_at": null,
    "message": "Invalid request",
    "review_notes": null,
    "failure_reason": "Provider API error"
  }
}
```

## Installation

### Prerequisites

- PHP 8.2 or higher
- Composer
- SQLite (for development) or MySQL/PostgreSQL (for production)
- Node.js & NPM (for asset compilation)

### Step-by-Step Setup

1. **Clone the repository and install dependencies:**
   ```bash
   git clone <repository-url>
   cd mexar-microservice-kyc
   composer install
   npm install
   ```

2. **Copy the environment file and configure:**
   ```bash
   cp .env.example .env
   ```

3. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

4. **Configure environment variables** (see [Configuration](#configuration) section)

5. **Run database migrations:**
   ```bash
   php artisan migrate
   ```

6. **Create your first user and API key:**
   ```bash
   php artisan mexar:create-user
   ```

7. **Register webhooks with RegTank** (if using RegTank):
   ```bash
   php artisan app:webhook:register --isEnabled=true
   ```

8. **Start the development server:**
   ```bash
   composer dev
   ```
   This runs all services concurrently (web server, queue worker, logs, and vite).

## Configuration

### Required Environment Variables

Create a `.env` file with the following configurations:

#### Application Settings
```dotenv
APP_NAME="MEXAR KYC MSA"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
```

#### Database Configuration
```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mexar_kyc
DB_USERNAME=root
DB_PASSWORD=
```

#### RegTank Provider Configuration
```dotenv
COMPANY_SPECIFIC_REGTANK_SERVICE_URL=https://api.regtank.com/company-specific
REGTANK_CRM_SERVER_URL=https://api.regtank.com/crm
CLIENT_ID_TEMPLATE=your-client-id-{COMPANY_SPECIFIC_ID}
CLIENT_SECRET_TEMPLATE=your-client-secret-{COMPANY_SPECIFIC_ID}
REGTANK_ASIGNEE=your-assignee-id
```

#### GlairAI Provider Configuration
```dotenv
GLAIR_OCR_BASE_URL=https://api.glair.ai
GLAIR_API_KEY=your-glair-api-key
GLAIR_USERNAME=your-glair-username
GLAIR_PASSWORD=your-glair-password
```

#### Queue Configuration
```dotenv
QUEUE_CONNECTION=database
```

### Multi-API-Key Configuration

Each client can have multiple API keys with different webhook URLs:

```bash
# Create a new user
php artisan mexar:create-user

# The user can then create multiple API keys via your admin interface
# Each API key can have:
# - name: "Production Key", "Staging Key", etc.
# - api_key: auto-generated unique key
# - webhook_url: "https://client.com/webhook/production"
# - signature_key: optional key for webhook signature verification
```

## API Reference

### Authentication

All API requests require an API key passed in the `X-API-KEY` header:

```bash
curl -X POST https://kyc-msa.example.com/api/screen \
  -H "X-API-KEY: your-api-key-here" \
  -H "Content-Type: application/json" \
  -d '{ ... }'
```

### Endpoints

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/api/screen` | Submit KYC screening request |
| `GET` | `/api/status/{uuid}` | Get screening status |
| `POST` | `/api/e-form-kyb` | Submit KYB screening (company) |
| `POST` | `/api/v1/ocr` | Process OCR for KTP/Passport |

### Webhook Endpoints (Incoming)

| Method | Endpoint | Description |
|--------|----------|-------------|
| `POST` | `/webhooks/kyc` | RegTank KYC result webhook |
| `POST` | `/webhooks/djkyb` | RegTank KYB result webhook |
| `POST` | `/webhooks/liveness` | RegTank liveness result webhook |

### Status Values

- `pending`: Screening in progress
- `approved`: KYC approved
- `rejected`: KYC rejected
- `error`: Processing error occurred
- `unresolved`: Requires manual review

### Supported Providers

- `regtank`: RegTank Dow Jones screening
- `glair`: GlairAI identity verification (Indonesian documents)
- `test`: Test mode (returns mock data)

## Testing

### Run All Tests
```bash
php artisan test
```

### Run Specific Test Suites
```bash
# KYC status endpoint tests
php artisan test --filter=KycStatusTest

# GlairAI async flow tests
php artisan test --filter=GlairAIAsyncFlowTest

# Feature tests only
php artisan test --testsuite=Feature
```

### Test Coverage

- ✅ Status polling endpoint
- ✅ Multi-API-key authentication
- ✅ Async workflow for all providers
- ✅ Webhook notifications
- ✅ Error handling
- ✅ Job processing

## Development

### Development Server

Run all services at once:
```bash
composer dev
```

This executes:
- `php artisan serve` - Web server
- `php artisan queue:listen --tries=1` - Queue worker
- `php artisan pail --timeout=0` - Log viewer
- `npm run dev` - Vite asset compilation

### Code Quality

Format code with Laravel Pint:
```bash
./vendor/bin/pint

# Format specific files
./vendor/bin/pint app/Http/Controllers
```

### Generate API Documentation

Documentation is auto-generated using Scribe:
```bash
php artisan scribe:generate
```

View documentation at: `http://localhost:8000/docs`

### Useful Commands

```bash
# Create a new user with API key
php artisan mexar:create-user

# Generate JWT token
php artisan mexar:generate-jwt-token

# Register/enable webhooks with RegTank
php artisan app:webhook:register --isEnabled=true

# Monitor queue jobs
php artisan queue:work --verbose

# View logs in real-time
php artisan pail
```

## Architecture Decisions

### Why Async-First?

All KYC providers operate asynchronously in production:
- **RegTank**: Takes minutes to hours, uses webhooks
- **GlairAI**: Real-time API but processed in background jobs for consistency
- **Consistency**: Same client experience regardless of provider

### Why Multi-API-Key Architecture?

- **Multi-environment**: Clients can have separate keys for dev/staging/prod
- **Webhook flexibility**: Each key can have its own webhook URL
- **Security**: Keys can be rotated independently
- **Soft delete**: Keys can be disabled without losing history

### Why Service Factory Pattern?

- **Testability**: Easy to mock providers in tests
- **Extensibility**: Add new providers by implementing `KYCServiceInterface`
- **Dependency Injection**: Leverage Laravel's container for dependencies

## Contributing

1. Follow PSR-12 coding standards
2. Write tests for new features
3. Update API documentation with Scribe attributes
4. Run `./vendor/bin/pint` before committing
5. Ensure all tests pass: `php artisan test`

## License

Proprietary - AbleGroup Singapore

## Support

For issues and questions, contact the development team or create an issue in the repository.
