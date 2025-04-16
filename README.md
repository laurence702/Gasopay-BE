# Gasopay API

A Laravel-based API for managing product payments

## Requirements

- PHP 8.2 or higher
- Composer
- MySQL 8.0 or higher
- Node.js and NPM (for frontend assets)
- Docker and Docker Compose (optional, for containerized development)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/gasopay-api.git
cd gasopay-api
```

2. Install PHP dependencies:
```bash
composer install
```

3. Create environment file:
```bash
cp .env.example .env
```

4. Generate application key:
```bash
php artisan key:generate
```

5. Configure your database in `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_db
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

6. Run migrations:
```bash
php artisan migrate
```

7. Seed the database (optional):
```bash
php artisan db:seed
```

## Development with Docker

1. Start the containers:
```bash
docker compose up -d
```

2. Install dependencies inside the container:
```bash
docker compose exec app composer install
```

3. Generate application key:
```bash
docker compose exec app php artisan key:generate
```

4. Run migrations:
```bash
docker compose exec app php artisan migrate
```

## Testing

Run the test suite:
```bash
# Using PHPUnit directly
./vendor/bin/phpunit

# Using Laravel's test command
php artisan test

# Using Docker
docker compose exec app php artisan test
```

Run specific test files:
```bash
php artisan test tests/Feature/Controllers/Api/AuthControllerTest.php
```

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details. 