# MEXAR MSA KYC

This is a microservice for KYC (Know Your Customer) operations, built using Laravel. It provides endpoints for managing personal information, document uploads, and webhook notifications.

## Features

- KYC
- KYB
- OCR
- Webhook notifications
- Webhook registration

## Installation

1. install dependencies using Composer:
   ```bash
   composer install
   ```

2. copy the `.env.example` file to `.env` and configure your environment variables:
   ```bash
    cp .env.example .env
    ```

3. generate the application key:
    ```bash
    php artisan key:generate
    ```

4. run the migrations to set up the database:
    ```bash
    php artisan migrate
    ```
5. register the webhook by running the command:
    ```bash
    php artisan mexar:enable-webhook
    ```
