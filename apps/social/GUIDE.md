# Sinapp Social - System Guide 📘

Welcome to **Sinapp Social**, a multi-platform social media automation bot designed to manage engagement, content creation, and analytics primarily for X (formerly Twitter).

---

## 🏗 System Architecture


This project is built on a **Custom PHP MVC** architecture (lightweight, no framework) running in a Dockerized environment.

### 1. Technical Stack
- **Backend:** PHP 8.2 (PSR-4 Autoloading)
- **Database:** MySQL 8.0
- **Web Server:** Nginx (Alpine) + PHP-FPM
- **Frontend:** Vanilla PHP Views + TailwindCSS / Bootstrap
- **Containerization:** Docker & Docker Compose

### 2. Project Structure
- **`src/`**: Core application logic.
  - **`Core/`**: Kernel, Router, Database connection, Helper classes.
  - **`Controllers/`**: Handles HTTP requests and renders views.
  - **`Models/`**: Database interactions (Active Record pattern).
  - **`Services/`**: Business logic integrations (e.g., `XService`, `GeminiService`).
- **`views/`**: Frontend templates.
- **`public/`**: Web root entry point (`index.php`).
- **`database/migrations/`**: SQL migration files.
- **`docker/`**: Configuration for Nginx, PHP, and other services.

---

## 🛠 Developer Setup

### Prerequisites
- Docker & Docker Compose installed.
- Git.

### Installation Steps
1.  **Clone the Repository**
    ```bash
    git clone <repo-url>
    cd sinapp-social
    ```

2.  **Environment Configuration**
    Copy the example environment file and configure your keys:
    ```bash
    cp .env.example .env
    ```
    *Update `.env` with your X API Credentials and Database settings.*

3.  **Start the Application**
    Build and run the containers:
    ```bash
    docker-compose up -d --build
    ```
    *Note: The `entrypoint.sh` script will automatically run database migrations on startup.*

4.  **Access the App**
    - **Frontend:** [http://localhost:9090](http://localhost:9090)
    - **Database (Internal):** Host: `db`, Port: `3306`

### ⚡️ Quick Commands (Makefile)
Instead of typing long Docker commands, you can use:

- `make setup`: First time setup (build, up, install dependencies, migrate)
- `make up`: Start containers
- `make start`: Alias for up
- `make stop`: Stop containers
- `make restart`: Restart containers
- `make install`: Run composer install
- `make migrate`: Run database migrations
- `make logs`: Tail container logs
- `make shell`: Enter the app container bash

---

## ⚖️ Operational Rules & Maintenance

> **CRITICAL:** Do not run PHP commands on your local machine. Always use the Docker container.

### 1. Running Commands
**Incorrect:** `php bin/console.php migrate`  
**Correct:** 
```bash
docker-compose exec app php bin/console.php migrate
```

### 2. Dependency Management
To install or update PHP packages:
```bash
docker-compose exec app composer install
```

### 3. File Permissions
If you encounter write permission errors (logs/storage):
```bash
docker-compose exec app chmod -R 775 storage
```

### 4. Database Migrations
Generic migration command:
```bash
docker-compose exec -T db mysql -u root -p"$DB_ROOT_PASSWORD" "$DB_DATABASE" < database/migrations/YOUR_MIGRATION_FILE.sql
```

---

## 📖 User Manual

### 1. Getting Started
- **Registration:** Access the app and register a new account.
- **Login:** Use your credentials to access the Dashboard.

### 2. Connecting Accounts
1. Navigate to **"X Accounts"** in the sidebar.
2. Click **"Link New Account"**.
3. You will be redirected to X (Twitter) to authorize the application. 
   *(Uses OAuth 2.0 Secure Flow)*.

### 3. Configuring Automation
1. **Target Users:** Go to "Target Users" and add X handles you want the bot to monitor or interact with.
2. **AI Tone:** Select the personality for the bot:
   - **Supportive:** Positive, community-building interactions.
   - **Ironic/Critical:** Edgy, engagement-driving replies.

### 4. Monitoring
- **Dashboard:** View real-time stats on posts, replies, and system health.
- **Logs:** Check "System Logs" for detailed activity reports and error tracking.

### 5. Limits & Quotas (Dynamic)
> [!NOTE]
> Daily post and reply limits are determined by your subscribed **Plan**.
> - **Starter**: 3 Posts / 10 Replies
> - **Pro**: 10 Posts / 50 Replies
> - **Agency**: 50 Posts / 200 Replies
>
> *(These limits can be adjusted by the Admin via the Plans Dashboard.)*

---

## 🔐 API Configuration Reference

To enable full functionality, ensure your `.env` has:

```ini
# Database
DB_HOST=db
DB_DATABASE=sinapp_social
DB_USERNAME=user
DB_PASSWORD=secret

# X (Twitter) API
X_CLIENT_ID=...
X_CLIENT_SECRET=...
X_CALLBACK_URL=http://localhost:9090/accounts/callback

# AI Services
GEMINI_API_KEY=...
```
