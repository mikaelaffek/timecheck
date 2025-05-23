# Timetjek Backend

This is the Laravel 11 backend for the Timetjek time tracking application.

## Requirements

- PHP 8.3+
- Composer
- MariaDB

## Setup

1. Install dependencies:
   ```
   composer install
   ```

2. Set up environment:
   ```
   cp .env.example .env
   php artisan key:generate
   ```

3. Configure database in `.env` file:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=timetjek
   DB_USERNAME=timetjek
   DB_PASSWORD=timetjek
   ```

4. Run migrations and seeders:
   ```
   php artisan migrate --seed
   ```

5. Start the development server:
   ```
   php artisan serve
   ```

## API Endpoints

The backend provides the following API endpoints:

- Authentication
  - POST /api/login
  - POST /api/logout
  - POST /api/register

- Users
  - GET /api/users
  - POST /api/users
  - GET /api/users/{id}
  - PUT /api/users/{id}
  - DELETE /api/users/{id}

- Time Registrations
  - GET /api/time-registrations
  - POST /api/time-registrations
  - GET /api/time-registrations/{id}
  - PUT /api/time-registrations/{id}
  - DELETE /api/time-registrations/{id}

- Reports
  - GET /api/reports/time
  - GET /api/reports/staff-registry

## Database Schema

The database includes the following main tables:

- users
- time_registrations
- schedules
- overtime_rules
- departments
