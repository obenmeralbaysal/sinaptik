# Sinapp Social - Session Memory

This file summarizes the work completed during this session to facilitate continuing the project on another machine.

## 🚀 Completed Benchmarks

### 1. Admin Panel & Dynamic Paywall
- **Admin Dashboard**: Implemented statistics (users, plans, accounts) and management interface.
- **Plan Management**: Fully functional CRUD for subscription plans.
- **Dynamic Landing Page**: Pricing section now pulls real-time data from the `plans` table.
- **Admin Access**: Role-based access control implemented. `test@test.com` is auto-promoted to admin for testing.

### 2. UI & UX Refinements
- **Professional Sidebar**: Replaced emojis with Font Awesome 6 icons.
- **Terminology**: Renamed "Social Accounts" to **"X Accounts"** for platform clarity.
- **Theme Support**: Unified dark/light theme consistency across internal and auth pages.

### 3. Security & X OAuth 2.0
- **OAuth 2.0 Integration**: Removed manual API key entries. Implemented centralized X API flow using **OAuth 2.0 with PKCE**.
- **XService**: Backend service for authorization, token exchange, and token refreshing.
- **Secure Storage**: Updated `accounts` table to store `access_token`, `refresh_token`, and `expires_at`.

### 4. Docker & Database Automation
- **Automatic Migrations**: Implemented a `Migrator` system.
- **Docker Entrypoint**: Created `entrypoint.sh` to run migrations automatically on container start (`docker-compose up`).
- **Database Alignment**: Fixed various schema mismatches between models and the actual database.

## 📜 Project Rules & Operational Guidelines
(Derived from `rules.md`)

### 1. Docker-Only Execution
> **CRITICAL:** All PHP and console commands must be executed **inside the Docker container**.
- **Incorrect:** `php bin/console.php ...`
- **Correct:** `docker-compose exec app php bin/console.php ...`
- **Composer:** `docker-compose exec app composer install`

### 2. Database & Migrations
- **Location:** `/database/migrations/`
- **Naming:** `YYYY_MM_DD_HHMMSS_description.sql`
- **Manual Execution:**
  ```bash
  docker-compose exec -T db mysql -u root -p"$DB_ROOT_PASSWORD" "$DB_DATABASE" < database/migrations/filename.sql
  ```
  *(Note: `entrypoint.sh` handles this automatically on startup)*

### 3. Security Protocols
- **.env**: Never commit to Git (use `.env.example`).
- **Encryption**: Encrypt sensitive API keys in the database.
- **DB Access**: App should use a specific user, not root.

### 4. File Permissions
- Ensure `storage` and `logs` are writable:
  `docker-compose exec app chmod -R 775 storage`

## 🛠️ Environment Configuration (Required)
Ensure your `.env` file contains the following keys for X integration:
```text
X_CLIENT_ID=your_client_id
X_CLIENT_SECRET=your_client_secret
X_CALLBACK_URL=http://localhost:9090/accounts/callback
```

## 📍 Next Steps
- [ ] **X User Info**: Enhance OAuth callback to fetch and store the actual X username instead of "X User".
- [ ] **AI Integration**: Connect the centralized AI logic to the newly authorized X accounts.
- [ ] **Subscription Flow**: Connect the "Get Started" buttons on the landing page to a payment/subscription checkout.

---
*Generated on 2026-02-02 by Antigravity*
