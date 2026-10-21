# Order Processing System API

Laravel REST API for managing orders and payments with JWT auth and an extensible Strategy-based payment layer.

**Stack:** PHP 8.2 · Laravel 12 · MySQL · JWT (`tymon/jwt-auth`) · PHPUnit

**Repo:** [github.com/Mohamedadelmabrouk/order-processing-system](https://github.com/Mohamedadelmabrouk/order-processing-system)

---

## Key Features

- JWT authentication (register, login, logout, profile)
- Order CRUD with items, totals, status filters & pagination
- User ownership isolation on orders and payments
- Pluggable payment gateways: Credit Card, PayPal, Stripe (simulated)
- Business rules: pay only `confirmed` orders · block delete when payments exist
- Unified JSON responses + Form Request validation
- Unit & Feature tests + Postman collection

---

## Architecture

Layered flow: **Controller → Service → Repository → Model**.

Payments use the **Strategy Pattern** (`PaymentGatewayInterface` + Factory) so new gateways need minimal code changes.

| Pattern | Usage |
|---------|--------|
| **Strategy** | Payment gateways (`credit_card`, `paypal`, `stripe`) |
| **Repository** | Data access layer (User, Order, Payment, Gateway) |
| **Service Layer** | Business rules & orchestration |
| **DTO** | `PaymentPayload` / `PaymentResult` for gateway I/O |
| **Factory** | Resolves the active gateway strategy by name |

---

## Getting Started

```bash
git clone https://github.com/Mohamedadelmabrouk/order-processing-system.git
cd order-processing-system
composer install
cp .env.example .env
php artisan key:generate
php artisan jwt:secret
```

Configure DB in `.env`, create the database, then:

```bash
php artisan migrate
php artisan db:seed --class=PaymentGatewaySeeder
php artisan serve
```

API base: `http://127.0.0.1:8000/api`

> Requires PHP `sodium` enabled (for JWT).

---

## Tests

```bash
php artisan test
```

```bash
php artisan test --testsuite=Unit
php artisan test --testsuite=Feature
```

---

## Postman

Import: [`docs/postman/Order Processing System API.postman_collection.json`](docs/postman/Order%20Processing%20System%20API.postman_collection.json)

Set `base_url` to `http://127.0.0.1:8000/api`, then run Login/Register (token is saved automatically).
