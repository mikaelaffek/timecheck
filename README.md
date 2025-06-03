# Timetjek - Employee Time Tracking Application

## What is Timetjek?

Timetjek is a modern web application for tracking employee work hours. It allows employees to clock in/out and managers to review time registrations. The application features a clean, intuitive interface built with Vuetify components.

## Key Features

- Simple clock in/out functionality from the dashboard
- View recent time registrations
- Edit time entries (adjust clock in/out times)
- User authentication with personal ID and password
- Admin view for managing employee time records
- Profile and password management

## Technology Stack

### Backend
- Laravel 11 (PHP 8.3)
- MariaDB database
- Laravel Sanctum for API authentication
- RESTful API architecture

### Frontend
- Vue 3 with Composition API
- TypeScript
- Vuetify 3 for UI components
- Pinia for state management
- Vue Router for navigation

## Docker Installation

### Prerequisites
- Docker and Docker Compose

### Installation Steps

1. **Clone the repository**
   ```bash
   git clone <repository-url>
   cd timetjek
   ```

2. **Start Docker containers**
   ```bash
   docker-compose up -d
   ```

3. **Set up backend**
   ```bash
   # Install dependencies
   docker-compose exec backend composer install
   
   # Generate application key
   docker-compose exec backend php artisan key:generate
   
   # Run migrations and seed database
   docker-compose exec backend php artisan migrate --seed
   ```

4. **Set up frontend**
   ```bash
   # Install dependencies
   docker-compose exec frontend npm install
   
   # Build for production
   docker-compose exec frontend npm run build
   # OR for development with hot reload
   docker-compose exec frontend npm run dev
   ```

5. **Access the application**
   - Frontend: http://localhost:3000
   - Backend API: http://localhost:8000

### Default Login
```
Personal ID: 870531-4139  # Admin user (previously ADMIN001)
Password: password

# Other available test users:
# 790215-3391 (Manager, previously MGR001)
# 850612-5578 (Employee, previously EMP001)
# 890423-6644 (Employee, previously EMP002)
# 920718-4433 (Employee, previously EMP003)
```

## Important Notes

- **Always use Docker**: This application must be run using Docker containers. Running directly on the host machine will cause dependency issues.
- **API Testing**: A Postman collection is included in the root directory for testing API endpoints.
- **Sample Data**: The database seeder creates test users and time registrations for demonstration.
