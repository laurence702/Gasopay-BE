# GasoPay Backend: Development Workflow

This document outlines the recommended development workflow, environment setup, and deployment process for the GasoPay Laravel backend.

## Table of Contents

- [GasoPay Backend: Development Workflow](#gasopay-backend-development-workflow)
  - [Table of Contents](#table-of-contents)
  - [1. Environment Setup](#1-environment-setup)
    - [1.1. Prerequisites](#11-prerequisites)
    - [1.2. Local Setup (using Docker)](#12-local-setup-using-docker)
    - [1.3. Local Setup (Manual/Without Docker)](#13-local-setup-manualwithout-docker)
    - [1.4. Environment Variables (`.env`)](#14-environment-variables-env)
  - [2. Git Workflow](#2-git-workflow)
    - [2.1. Branches](#21-branches)
    - [2.2. Commits](#22-commits)
    - [2.3. Pull Requests (PRs)](#23-pull-requests-prs)
    - [2.4. Merging](#24-merging)
  - [3. Coding Standards](#3-coding-standards)
    - [3.1. PSR Standards](#31-psr-standards)
    - [3.2. Naming Conventions](#32-naming-conventions)
    - [3.3. Linting and Formatting](#33-linting-and-formatting)
  - [4. Testing](#4-testing)
    - [4.1. Running Tests](#41-running-tests)
    - [4.2. Writing Tests](#42-writing-tests)
  - [5. Deployment Process](#5-deployment-process)
    - [5.1. Overview (Render)](#51-overview-render)
    - [5.2. Staging Environment (Assumed)](#52-staging-environment-assumed)
    - [5.3. Production Environment](#53-production-environment)
    - [5.4. Key Deployment Steps (General)](#54-key-deployment-steps-general)

## 1. Environment Setup

### 1.1. Prerequisites

- PHP (version as per `composer.json`, likely >= 8.1)
- Composer
- Node.js & npm (for frontend assets if any, or dev tools like Vite)
- Docker & Docker Compose (recommended for local development)
- Git
- Access to the project repository.

### 1.2. Local Setup (using Docker)

The presence of `Dockerfile` and `docker-compose.yml` suggests a Docker-based local development environment is preferred.

1.  **Clone the repository:**
    ```bash
    git clone <repository_url>
    cd api # Or your project root
    ```
2.  **Copy environment file:**
    ```bash
    cp .env.example .env
    ```
3.  **Configure `.env`:** Update database credentials, application URL, mail settings, and any third-party API keys. Ensure `DB_HOST` points to the Docker database service name (e.g., `mysql` or `pgsql`).
4.  **Build and run Docker containers:**
    ```bash
    docker-compose build
    docker-compose up -d
    ```
5.  **Install Composer dependencies:**
    ```bash
    docker-compose exec app composer install
    ```
6.  **Generate application key:**
    ```bash
    docker-compose exec app php artisan key:generate
    ```
7.  **Run database migrations and seeders (if any):
    ```bash
    docker-compose exec app php artisan migrate --seed
    ```
8.  **Link storage (if needed):
    ```bash
    docker-compose exec app php artisan storage:link
    ```
9.  **Install NPM dependencies (if applicable):
    ```bash
    docker-compose exec app npm install
    docker-compose exec app npm run dev # or build
    ```
10. **Access the application:** Typically at `http://localhost:<PORT>` as defined in `docker-compose.yml` or your web server config.

### 1.3. Local Setup (Manual/Without Docker)

1.  Clone the repository.
2.  Ensure PHP, Composer, Node.js, and a database server (e.g., MySQL, PostgreSQL) are installed and running.
3.  Copy `.env.example` to `.env` and configure it for your local environment (database, mail, etc.).
4.  Install Composer dependencies: `composer install`.
5.  Generate application key: `php artisan key:generate`.
6.  Run database migrations: `php artisan migrate --seed`.
7.  Link storage: `php artisan storage:link`.
8.  Install NPM dependencies and build assets if applicable: `npm install && npm run dev`.
9.  Configure your local web server (Nginx, Apache) to point to the `public` directory.

### 1.4. Environment Variables (`.env`)

Key variables to configure:
- `APP_NAME`, `APP_ENV`, `APP_KEY`, `APP_DEBUG`, `APP_URL`
- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_ENCRYPTION`, `MAIL_FROM_ADDRESS`
- Cache, Queue, Session drivers
- Third-party service keys (e.g., Africa's Talking, AWS S3 if used)

## 2. Git Workflow

A standard feature-branch workflow is recommended.

### 2.1. Branches

- **`main` / `master`:** Represents the production-ready code. Should always be stable. Direct pushes should be restricted.
- **`develop`:** Integration branch for features. This is where features are merged before being promoted to `main` (often via a release branch).
- **`feature/<feature-name>`:** Create a new branch for each new feature or bugfix (e.g., `feature/user-authentication`, `fix/order-processing-bug`). Branched off `develop`.
- **`release/<version>` (Optional):** Used for preparing a new production release. Branched from `develop`, allows for final testing and bug fixes before merging to `main` and tagging.
- **`hotfix/<issue-name>` (Optional):** For critical bugs in production. Branched from `main`, fixed, then merged back into both `main` and `develop`.

### 2.2. Commits

- Commit messages should be clear, concise, and follow a consistent style (e.g., Conventional Commits: `feat: add user registration endpoint`).
- Commit frequently with small, logical changes.

### 2.3. Pull Requests (PRs)

- When a feature or fix is complete, push the branch to the remote repository and open a Pull Request against the `develop` branch (or `main` for hotfixes).
- PR descriptions should explain the changes, why they were made, and how to test them.
- Ensure automated checks (CI pipeline, tests) pass.
- Require at least one code review from another team member.

### 2.4. Merging

- Prefer squash merging or rebase and merge for a cleaner Git history on `develop` and `main` branches.
- Delete feature branches after merging.

## 3. Coding Standards

### 3.1. PSR Standards

- Follow PSR-1, PSR-12, and PSR-4 for PHP code style and autoloading.

### 3.2. Naming Conventions

- **Variables:** `camelCase`
- **Methods:** `camelCase`
- **Classes:** `PascalCase`
- **Database Tables:** `snake_case`, plural (e.g., `payment_histories`)
- **Database Columns:** `snake_case`
- **Routes:** `kebab-case` (e.g., `user-profiles`)
- **Config/Env Variables:** `UPPER_SNAKE_CASE`

### 3.3. Linting and Formatting

- Use tools like PHP-CS-Fixer or Pint to automatically enforce coding standards.
- Configure your IDE to follow the project's coding standards.

## 4. Testing

### 4.1. Running Tests

- **PHPUnit:** The primary testing framework.
- Run all tests:
  ```bash
  # Using Docker
  docker-compose exec app php artisan test
  
  # Without Docker
  ./vendor/bin/phpunit # or php artisan test
  ```
- Run specific tests or files as needed.

### 4.2. Writing Tests

- **Feature Tests (`tests/Feature`):** Test application features from an outside perspective (e.g., API endpoint tests).
- **Unit Tests (`tests/Unit`):** Test individual classes, methods, or components in isolation.
- Aim for good test coverage of critical application logic.
- Use factories to create model instances for testing.
- Follow Arrange-Act-Assert (AAA) pattern in tests.

## 5. Deployment Process

### 5.1. Overview (Render)

The presence of `render.yaml` indicates that the application is likely deployed on [Render](https://render.com/). This file defines services, build commands, environment variables, etc.

### 5.2. Staging Environment (Assumed)

- It's highly recommended to have a staging environment that mirrors production.
- Deploy to staging first, perform User Acceptance Testing (UAT).
- The `render.yaml` might have configurations for a staging service, or a separate configuration file/branch might be used.

### 5.3. Production Environment

- Deployment to production should be a controlled process, ideally after successful staging deployment and testing.
- Render likely handles deployments automatically when changes are pushed/merged to the production branch (e.g., `main`).

### 5.4. Key Deployment Steps (General, adapted for Render)

1.  **Merge changes:** Ensure the `develop` branch is up-to-date and stable. Merge `develop` into `main` (or a `release` branch which is then merged to `main`).
2.  **Push to Git:** Push the `main` branch to the remote repository.
3.  **Render Deployment (Automatic):**
    - Render will detect changes to the connected Git branch (likely `main`).
    - It will execute the build command specified in `render.yaml` (e.g., `php artisan optimize:clear && php artisan config:cache && php artisan route:cache && php artisan view:cache && php artisan event:cache && composer install --optimize-autoloader --no-dev`).
    - Run database migrations (this might be a separate job or part of the release command in `render.yaml`, e.g., `php artisan migrate --force`).
    - Start the application server (e.g., `php-fpm`, `nginx`, as defined in `Dockerfile` or `render.yaml` start command `start.sh`).
4.  **Post-Deployment Checks:**
    - Monitor application logs on Render.
    - Verify key functionalities on the live environment.

**Important commands for Laravel deployment:**
- `composer install --optimize-autoloader --no-dev`
- `php artisan config:cache`
- `php artisan route:cache`
- `php artisan view:cache` (if views are used)
- `php artisan event:cache`
- `php artisan migrate --force` (in production)
- `php artisan optimize` (combines several caching commands)

This workflow aims to ensure smooth development, collaboration, and reliable deployments. 