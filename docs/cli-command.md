# CLI Commands

This document describes the CLI commands available for user management in the KYC Microservice.

## User Management Commands

### Create User: `app:user:new`

Create a new user with login credentials and API key for KYC API access.

**Signature:**
```bash
php artisan app:user:new [options]
```

**Options:**

| Option | Description | Required |
|--------|-------------|----------|
| `--name` | User name | Yes |
| `--email` | User email for login | Yes |
| `--password` | User password for login | Yes |
| `--api-key-name` | API key name (default: "Default") | No |
| `--api-key` | API key value (auto-generated if not provided) | No |
| `--webhook-url` | Webhook URL for receiving KYC results | No |
| `--signature-key` | Signature key for webhook verification | No |

**Interactive Mode:**

Run without options to be prompted for each required field:

```bash
php artisan app:user:new
```

Example output:
```
Please enter the user name: Company ABC
Please enter the user email: api@company.com
Please enter the password:
Please confirm the password:
Enter webhook URL for receiving KYC results (optional, press Enter to skip): https://api.company.com/webhooks/kyc

User created successfully!

+-----------+------------------+
| Field     | Value            |
+-----------+------------------+
| User ID   | 1                |
| Name      | Company ABC      |
| Email     | api@company.com  |
| User Type | user             |
+-----------+------------------+

API Key created:

+---------------+----------------------------------------------+
| Field         | Value                                        |
+---------------+----------------------------------------------+
| API Key Name  | Default                                      |
| API Key       | xK7mN9pQ2rS5tU8vW0xY3zA6bC9dE2fG...         |
| Webhook URL   | https://api.company.com/webhooks/kyc         |
| Signature Key | (not set)                                    |
+---------------+----------------------------------------------+

⚠️  Please save the API key securely. It will not be shown again.
```

**Non-Interactive Mode:**

Provide all required options via command line:

```bash
php artisan app:user:new \
    --name="Company ABC" \
    --email="api@company.com" \
    --password="securePassword123" \
    --api-key-name="Production" \
    --webhook-url="https://api.company.com/webhooks/kyc"
```

---

### Create Admin: `app:admin:new`

Create a new admin user for dashboard access.

**Signature:**
```bash
php artisan app:admin:new [options]
```

**Options:**

| Option | Description | Required |
|--------|-------------|----------|
| `--name` | Admin name | Yes |
| `--email` | Admin email for login | Yes |
| `--password` | Admin password for login | Yes |

**Admin Limit:**

The system enforces a maximum number of admin users, configured via the `MAX_ADMIN_USERS` environment variable (default: 1).

```env
MAX_ADMIN_USERS=1
```

**Interactive Mode:**

Run without options to be prompted for each required field:

```bash
php artisan app:admin:new
```

Example output:
```
Please enter the admin name: Super Admin
Please enter the admin email: admin@example.com
Please enter the password:
Please confirm the password:

Admin user created successfully!

+------------+-----------------------+
| Field      | Value                 |
+------------+-----------------------+
| User ID    | 1                     |
| Name       | Super Admin           |
| Email      | admin@example.com     |
| User Type  | admin                 |
| Created At | 2026-01-08 08:00:00   |
+------------+-----------------------+
```

**Non-Interactive Mode:**

Provide all required options via command line:

```bash
php artisan app:admin:new \
    --name="Super Admin" \
    --email="admin@example.com" \
    --password="securePassword123"
```

**Error: Admin Limit Reached**

If the maximum admin limit is reached, the command will fail:

```
Maximum admin limit (1) reached. Cannot create more admin users.
```

---

## Validation Rules

Both commands enforce the following validation:

| Field | Validation |
|-------|------------|
| Name | Required, non-empty |
| Email | Required, valid email format, unique |
| Password | Required, non-empty |

In interactive mode, password confirmation is required to prevent typos.
