# Webhook Security

This document explains how to verify webhook signatures to ensure the authenticity of webhook payloads sent by the KYC microservice.

## Overview

When your API key has a `signature_key` configured, all outgoing webhooks include cryptographic signatures that allow you to verify:

1. **Authenticity**: The webhook was sent by the KYC service (not a malicious actor)
2. **Integrity**: The payload has not been tampered with
3. **Freshness**: The webhook is recent (prevents replay attacks)

## Signature Headers

When `signature_key` is configured, webhooks include two additional headers:

| Header | Description | Example |
|--------|-------------|---------|
| `X-Webhook-Signature` | HMAC-SHA256 signature | `a1b2c3d4e5f6...` |
| `X-Webhook-Timestamp` | Unix timestamp (seconds) | `1704067200` |

## Signature Algorithm

The signature is computed as:

```
signature = HMAC-SHA256(timestamp + "." + payload_json, signature_key)
```

Where:
- `timestamp`: The value from `X-Webhook-Timestamp` header
- `payload_json`: The raw JSON request body (exact bytes)
- `signature_key`: Your API key's signature key

## Verification Steps

1. Extract the `X-Webhook-Signature` and `X-Webhook-Timestamp` headers
2. Get the raw request body (do not parse and re-serialize)
3. Compute the expected signature using your `signature_key`
4. Compare signatures using a timing-safe comparison
5. Optionally validate the timestamp is recent (within 5 minutes)

## Code Examples

### Node.js

```javascript
const crypto = require('crypto');

function verifyWebhookSignature(req, signatureKey) {
  const signature = req.headers['x-webhook-signature'];
  const timestamp = req.headers['x-webhook-timestamp'];
  const payload = req.rawBody; // Make sure to preserve raw body

  if (!signature || !timestamp) {
    return false; // Missing headers
  }

  // Verify timestamp is recent (5 minute tolerance)
  const currentTime = Math.floor(Date.now() / 1000);
  if (Math.abs(currentTime - parseInt(timestamp)) > 300) {
    return false; // Timestamp too old (possible replay attack)
  }

  // Compute expected signature
  const signedPayload = `${timestamp}.${payload}`;
  const expectedSignature = crypto
    .createHmac('sha256', signatureKey)
    .update(signedPayload)
    .digest('hex');

  // Timing-safe comparison
  return crypto.timingSafeEqual(
    Buffer.from(signature, 'hex'),
    Buffer.from(expectedSignature, 'hex')
  );
}

// Express.js usage
const express = require('express');
const app = express();

// Preserve raw body for signature verification
app.use('/webhooks/kyc', express.json({
  verify: (req, res, buf) => {
    req.rawBody = buf.toString();
  }
}));

app.post('/webhooks/kyc', (req, res) => {
  const SIGNATURE_KEY = process.env.KYC_SIGNATURE_KEY;

  if (!verifyWebhookSignature(req, SIGNATURE_KEY)) {
    console.error('Invalid webhook signature');
    return res.status(401).json({ error: 'Invalid signature' });
  }

  // Process verified webhook
  const { event, payload } = req.body;
  console.log('Verified webhook:', payload.msa_reference_id);

  res.status(200).json({ received: true });
});
```

### PHP

```php
<?php

function verifyWebhookSignature(
    string $signature,
    string $timestamp,
    string $payload,
    string $signatureKey
): bool {
    // Verify timestamp is recent (5 minute tolerance)
    $currentTime = time();
    if (abs($currentTime - (int) $timestamp) > 300) {
        return false; // Timestamp too old
    }

    // Compute expected signature
    $signedPayload = $timestamp . '.' . $payload;
    $expectedSignature = hash_hmac('sha256', $signedPayload, $signatureKey);

    // Timing-safe comparison
    return hash_equals($expectedSignature, $signature);
}

// Laravel usage
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class KYCWebhookController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $signature = $request->header('X-Webhook-Signature');
        $timestamp = $request->header('X-Webhook-Timestamp');
        $payload = $request->getContent(); // Raw body
        $signatureKey = config('services.kyc.signature_key');

        if (!$signature || !$timestamp) {
            return response()->json(['error' => 'Missing signature headers'], 401);
        }

        if (!verifyWebhookSignature($signature, $timestamp, $payload, $signatureKey)) {
            \Log::warning('Invalid webhook signature', [
                'signature' => substr($signature, 0, 20) . '...',
            ]);
            return response()->json(['error' => 'Invalid signature'], 401);
        }

        // Process verified webhook
        $data = $request->json()->all();
        \Log::info('Verified webhook', [
            'msa_reference_id' => $data['payload']['msa_reference_id'] ?? null,
        ]);

        return response()->json(['received' => true]);
    }
}
```

### Python

```python
import hmac
import hashlib
import time
from flask import Flask, request, jsonify

app = Flask(__name__)
SIGNATURE_KEY = "your-signature-key"

def verify_webhook_signature(signature: str, timestamp: str, payload: bytes, signature_key: str) -> bool:
    # Verify timestamp is recent (5 minute tolerance)
    current_time = int(time.time())
    if abs(current_time - int(timestamp)) > 300:
        return False  # Timestamp too old

    # Compute expected signature
    signed_payload = f"{timestamp}.{payload.decode('utf-8')}"
    expected_signature = hmac.new(
        signature_key.encode('utf-8'),
        signed_payload.encode('utf-8'),
        hashlib.sha256
    ).hexdigest()

    # Timing-safe comparison
    return hmac.compare_digest(expected_signature, signature)

@app.route('/webhooks/kyc', methods=['POST'])
def handle_kyc_webhook():
    signature = request.headers.get('X-Webhook-Signature')
    timestamp = request.headers.get('X-Webhook-Timestamp')
    payload = request.get_data()  # Raw body

    if not signature or not timestamp:
        return jsonify({'error': 'Missing signature headers'}), 401

    if not verify_webhook_signature(signature, timestamp, payload, SIGNATURE_KEY):
        return jsonify({'error': 'Invalid signature'}), 401

    # Process verified webhook
    data = request.get_json()
    print(f"Verified webhook: {data['payload']['msa_reference_id']}")

    return jsonify({'received': True}), 200
```

## Security Best Practices

### 1. Always Verify Signatures

Even in development environments, enable signature verification to catch integration issues early.

### 2. Use Timing-Safe Comparison

Always use timing-safe comparison functions (`crypto.timingSafeEqual`, `hash_equals`, `hmac.compare_digest`) to prevent timing attacks.

### 3. Validate Timestamps

Reject webhooks with timestamps older than 5 minutes to prevent replay attacks:

```javascript
const MAX_AGE_SECONDS = 300; // 5 minutes
const age = Math.abs(Date.now() / 1000 - parseInt(timestamp));
if (age > MAX_AGE_SECONDS) {
  // Reject: possible replay attack
}
```

### 4. Store Signature Keys Securely

- Use environment variables or secret managers
- Never commit signature keys to version control
- Rotate keys periodically

### 5. Log Verification Failures

Log failed verifications (without exposing the signature key) for security monitoring:

```javascript
if (!isValid) {
  console.error('Webhook verification failed', {
    timestamp,
    ip: req.ip,
    path: req.path,
  });
}
```

## Troubleshooting

### Signature Mismatch

**Common causes:**

1. **Payload modification**: Ensure you're using the raw request body, not a parsed/re-serialized version
2. **Wrong signature key**: Verify you're using the correct `signature_key` for this API key
3. **Character encoding**: Ensure UTF-8 encoding throughout

**Debug steps:**

```javascript
// Log intermediate values for debugging
console.log('Timestamp:', timestamp);
console.log('Raw payload:', payload.substring(0, 100));
console.log('Expected:', expectedSignature);
console.log('Received:', signature);
```

### Missing Headers

If `X-Webhook-Signature` and `X-Webhook-Timestamp` headers are missing:

1. Your API key may not have a `signature_key` configured
2. Contact the KYC service admin to configure your signature key

## Configuring Signature Key

When requesting your API key, you can:

1. **Provide your own**: Supply a key (minimum 16 characters) for use across systems
2. **Auto-generate**: Leave blank to have a secure 32-character key generated

To update or view your signature key, access the KYC Dashboard or contact the service admin.
