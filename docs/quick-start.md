# Quick Start Guide

Get your first KYC verification running in 5 minutes.

## Prerequisites

Before you begin, ensure you have:

1. **API Key** - Contact the KYC service admin to obtain your API key
2. **Webhook URL** (optional) - A publicly accessible HTTPS endpoint to receive results

## Environment URLs

| Environment | Base URL |
|-------------|----------|
| **Staging** | `https://kyc-staging.ablegroup.sg` |
| **Production** | `https://kyc.ablegroup.sg` |

---

## Step 1: Submit a KYC Request

Submit a verification request using `curl`:

```bash
curl -X POST https://kyc-staging.ablegroup.sg/api/v1/screen \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: your-api-key-here" \
  -d '{
    "personal_info": {
      "first_name": "John",
      "last_name": "Doe",
      "date_of_birth": "1990-01-15",
      "nationality": "SG"
    },
    "identification": {
      "id_type": "national_id",
      "id_number": "S1234567D",
      "issuing_country": "SG"
    },
    "address": {
      "address_line": "123 Orchard Road",
      "city": "Singapore",
      "country": "SG"
    },
    "meta": {
      "service_provider": "test",
      "reference_id": "my-internal-ref-001"
    }
  }'
```

**Response:**

```json
{
  "meta": {
    "code": 200,
    "message": "Screening successful",
    "request_id": "550e8400-e29b-41d4-a716-446655440000"
  },
  "data": {
    "identity": "550e8400-e29b-41d4-a716-446655440000"
  }
}
```

> **Note:** The `identity` value is your profile UUID. Save it for status checks.

---

## Step 2: Check Status

Poll the status endpoint to see your verification result:

```bash
curl -X GET https://kyc-staging.ablegroup.sg/api/v1/status/550e8400-e29b-41d4-a716-446655440000 \
  -H "X-API-KEY: your-api-key-here"
```

**Response (approved):**

```json
{
  "meta": {
    "code": 200,
    "message": "Status retrieved successfully"
  },
  "data": {
    "uuid": "550e8400-e29b-41d4-a716-446655440000",
    "status": "approved",
    "provider": "test",
    "provider_reference_id": "TEST-123456",
    "created_at": "2024-01-15T10:00:00Z",
    "updated_at": "2024-01-15T10:00:05Z"
  }
}
```

**Status values:**
- `pending` - Verification in progress
- `approved` - Identity verified
- `rejected` - Verification failed
- `error` - System error

---

## Step 3: Handle Webhooks (Recommended)

Instead of polling, configure a webhook URL to receive real-time notifications.

**Sample webhook payload:**

```json
{
  "event": "kyc.status.changed",
  "payload": {
    "msa_reference_id": "550e8400-e29b-41d4-a716-446655440000",
    "reference_id": "my-internal-ref-001",
    "status": "approved",
    "verified": true,
    "verified_at": "2024-01-15T10:00:05Z",
    "message": "KYC verification approved"
  }
}
```

**Webhook endpoint requirements:**
- HTTPS only (no HTTP)
- Respond with HTTP 200 within 10 seconds
- Publicly accessible

---

## Testing Different Outcomes

Use the `test` provider with `meta.status` to simulate different results:

```bash
# Simulate approved
curl -X POST https://kyc-staging.ablegroup.sg/api/v1/screen \
  -H "Content-Type: application/json" \
  -H "X-API-KEY: your-api-key-here" \
  -d '{
    "personal_info": { "first_name": "Test", "last_name": "User", "nationality": "SG" },
    "identification": { "id_type": "passport", "id_number": "TEST123", "issuing_country": "SG" },
    "address": { "address_line": "123 Test St", "city": "Singapore", "country": "SG" },
    "meta": {
      "service_provider": "test",
      "reference_id": "test-approved",
      "status": "approved"
    }
  }'

# Simulate rejected
# Change "status": "rejected" in meta

# Simulate error
# Change "status": "error" in meta
```

---

## Available Providers

| Provider | Code | Use Case |
|----------|------|----------|
| Test | `test` | Development & testing |
| RegTank | `regtank` | Production (SG, MY, ID) |
| GlairAI | `glair_ai` | Production (ID only) |

---

## Common Errors

| Error | Cause | Solution |
|-------|-------|----------|
| 401 Unauthorized | Invalid API key | Check `X-API-KEY` header |
| 422 Validation Error | Invalid payload | Check required fields |
| 404 Not Found | Invalid UUID | Verify profile UUID |

---

## API Tools

Import into your preferred API client:

| Tool | File | Description |
|------|------|-------------|
| **Postman** | [postman-collection.json](./postman-collection.json) | Import → Collection |
| **OpenAPI** | [openapi.yaml](./openapi.yaml) | Swagger/OpenAPI 3.0 spec |

**Postman Import:**
1. Open Postman → Import → Upload Files
2. Select `postman-collection.json`
3. Set the `X-API-KEY` header in collection variables

---

## Next Steps

- **Full API Reference**: See [Integration Guide](./integration-guideline.md)
- **Webhook Security**: See [Webhook Security](./webhook-security.md)
- **Workflow Examples**: See [API Workflows](./api-workflows-example.md)
- **Architecture**: See [How It Works](./how-the-microservice-work.md)
- **OpenAPI Spec**: See [openapi.yaml](./openapi.yaml)

---

## Need Help?

- **Email**: support@kyc-service.example.com
- **Dashboard**: Access your API keys and view profiles at `https://kyc.ablegroup.sg/dashboard`
