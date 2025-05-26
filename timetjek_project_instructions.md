# Timetjek Project Recreation Instructions

## Project Overview
This document provides instructions for recreating the Timetjek time registration application with the following modifications:
- No ReportController or OvertimeRuleController
- Schedule functionality merged into TimeRegistration
- Location associated with TimeRegistration
- Swedish personal number format (XXXXXX-XXXX) for login
- Feature-based structure for the frontend

## Technology Requirements
- **Backend**: PHP 8.3 and Laravel 11
- **Frontend**: TypeScript and Vue 3 (Vite)
- **Database**: MySQL
- **Deployment**: Docker

## Backend (Laravel)

### 1. Project Setup
1. Create a new Laravel project
2. Install Laravel Sanctum for authentication
3. Configure database connection

### 2. Models Structure
1. **User Model**
   - Fields: name, email, personal_id (Swedish format), password, role
   - Relationships: timeRegistrations, department
   - Methods: isAdmin(), isManager()

2. **Department Model**
   - Fields: name, description
   - Relationships: users

3. **Location Model**
   - Fields: name, address, latitude, longitude, radius
   - Relationships: timeRegistrations

4. **TimeRegistration Model** (with Schedule functionality merged)
   - Fields:
     - Basic: user_id, location_id, date, clock_in, clock_out, total_hours, latitude, longitude, notes, status
     - Schedule-related: scheduled_start, scheduled_end, is_recurring, recurrence_pattern
   - Relationships: user, location

### 3. Controllers
1. **AuthController**
   - login (using Swedish personal_id)
   - logout
   - register
   - user (get current user)

2. **UserController**
   - CRUD operations
   - updateProfile
   - updatePassword
   - getSettings/updateSettings

3. **DepartmentController**
   - CRUD operations

4. **LocationController**
   - CRUD operations
   - getNearby (optional)

5. **TimeRegistrationController** (with Schedule functionality)
   - CRUD operations
   - clockIn/clockOut
   - status (get current status)
   - recent (get recent registrations)
   - getByDate
   - getCurrentWeek
   - generateRecurring

### 4. API Routes
1. Public routes:
   - login
   - register

2. Protected routes (auth:sanctum middleware):
   - User management
   - Department management
   - Location management
   - Time registration management

### 5. Database Seeders
1. **DepartmentSeeder**
2. **LocationSeeder**
3. **UserSeeder** (with Swedish personal IDs)
   - Admin user: 870531-4139 / password
   - Manager user: 790215-3391 / password
   - Employee users: 
     - 850612-5578 / password
     - 890423-6644 / password
     - 920718-4433 / password
   - Test user: test123 / password

## Frontend (Vue.js with Vuetify)

### 1. Project Setup
1. Create a new Vue project with Vite
2. Install Vuetify, Vue Router, Pinia
3. Configure axios for API communication

### 2. Feature-Based Structure
```
src/
├── assets/
├── components/
├── features/
│   ├── auth/
│   │   ├── components/
│   │   ├── composables/
│   │   ├── pages/
│   │   │   ├── Login.vue
│   │   │   └── Register.vue
│   │   └── store/
│   ├── dashboard/
│   │   ├── components/
│   │   ├── composables/
│   │   ├── pages/
│   │   │   └── Dashboard.vue
│   │   └── store/
│   ├── time-tracking/
│   │   ├── components/
│   │   ├── composables/
│   │   ├── pages/
│   │   │   ├── TimeRegistrations.vue
│   │   │   └── Schedule.vue
│   │   └── store/
│   ├── user-management/
│   │   ├── components/
│   │   ├── composables/
│   │   ├── pages/
│   │   │   ├── Users.vue
│   │   │   └── Profile.vue
│   │   └── store/
│   └── location-management/
│       ├── components/
│       ├── composables/
│       ├── pages/
│       │   └── Locations.vue
│       └── store/
├── router/
├── stores/
└── utils/
```

### 3. UI Design Preferences
1. **Color Scheme**
   - Use default Vuetify styling instead of custom green color scheme
   - Avoid using green background color (#4ADE80) for:
     - Primary buttons
     - Login button
     - Top toolbar (v-app-bar)
   - Keep a clean, neutral UI

2. **Icons**
   - Use the latest Material Design Icons naming conventions
   - For example, use mdi-twitter-x instead of mdi-twitter to reflect Twitter's rebranding

### 4. Key Features to Implement
1. **Authentication**
   - Login with Swedish personal ID
   - Logout
   - Register (admin only)

2. **Dashboard**
   - Clock in/out functionality
   - Recent time registrations
   - Statistics overview
   - Handle "already clocked in" scenario with confirmation dialog

3. **Time Tracking**
   - View and manage time registrations
   - View and manage schedules
   - Filter by date, status
   - **Important**: Always use real data from the database, never use mock data

4. **User Management**
   - CRUD operations for users
   - User profile management
   - Password change

5. **Location Management**
   - CRUD operations for locations

## Docker Setup
1. Create a docker-compose.yml with the following services:

```yaml
version: '3.8'

services:
  # Backend service (PHP 8.3 & Laravel 11)
  backend:
    build:
      context: ./backend
      dockerfile: Dockerfile
    volumes:
      - ./backend:/var/www/html
      - ./backend/vendor:/var/www/html/vendor
    ports:
      - "8000:80"
    depends_on:
      db:
        condition: service_healthy
    environment:
      - DB_HOST=db
      - DB_PORT=3306
      - DB_DATABASE=timetjek
      - DB_USERNAME=timetjek
      - DB_PASSWORD=password

  # Frontend service (Vue 3 & TypeScript)
  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    volumes:
      - ./frontend:/app
      - ./frontend/node_modules:/app/node_modules
    ports:
      - "3000:3000"
    depends_on:
      - backend
    environment:
      - VITE_API_URL=http://localhost:8000/api

  # Database service (MySQL)
  db:
    image: mysql:8.0
    ports:
      - "3306:3306"
    environment:
      - MYSQL_DATABASE=timetjek
      - MYSQL_USER=timetjek
      - MYSQL_PASSWORD=password
      - MYSQL_ROOT_PASSWORD=rootpassword
    volumes:
      - db_data:/var/lib/mysql
    healthcheck:
      test: ["CMD", "mysqladmin", "ping", "-h", "localhost"]
      interval: 10s
      timeout: 5s
      retries: 5

volumes:
  db_data:
```

2. Create Dockerfiles for backend and frontend services:

**Backend Dockerfile (./backend/Dockerfile)**
```dockerfile
FROM php:8.3-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy existing application directory
COPY . /var/www/html

# Install dependencies
RUN composer install

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Configure Apache
RUN a2enmod rewrite
COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

EXPOSE 80
CMD ["apache2-foreground"]
```

**Frontend Dockerfile (./frontend/Dockerfile)**
```dockerfile
FROM node:20-alpine

WORKDIR /app

COPY package*.json ./

RUN npm install

COPY . .

EXPOSE 3000

CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
```

3. Configure environment variables for each service in their respective .env files

## Testing
1. Create test users with Swedish personal IDs
2. Test authentication flow
3. Test time registration features
4. Test user management features

## Deployment
1. Set up CI/CD pipeline (optional)
2. Configure production environment
3. Deploy to hosting provider

## Application Requirements (Applikationskrav)

### Swedish Requirements
* Kunna logga in med personnummer och lösenord
* Editering av användaruppgifter ( Byta lösenord )
* Stämpla in och ut enligt nuvarande tid ( Tidsregistreringar )
* Ha en överblick av sparade in och ut stämplingar
* Kunna spara enhetens nuvarande gps-kordinater på tidsregistreringarna
* Editeringsmöjligheter för sparade tidsregistreringar
* Enkel validering av tidsregistreringar så dessa inte överlappar med varandra

### English Translation
* Login with personal ID number and password
* Edit user information (change password)
* Clock in and out according to current time (Time registrations)
* Have an overview of saved clock-ins and clock-outs
* Save the device's current GPS coordinates with time registrations
* Editing capabilities for saved time registrations
* Simple validation of time registrations to prevent overlapping entries

### Critical Functionality Details
1. **Already Clocked In Handling**
   - When a user tries to clock in while already clocked in:
     - Show a confirmation dialog with the original clock-in time
     - If confirmed, clock out the user automatically
     - Then clock in again after a brief delay
     - This prevents the 422 "You are already clocked in" error

2. **Time Registration Validation**
   - Implement checks for overlapping time registrations
   - When creating or updating a time registration, verify it doesn't overlap with existing ones
   - Return appropriate error messages if overlaps are detected
   - Include the overlapping registration details in the error response

3. **Clock In/Out Pages**
   - **Dashboard Page (for individual users)**:
     - Clock in and clock out functionality
     - Display recent clock-ins and clock-outs for the logged-in user
     - Show current status (clocked in or out)
     - All data should be fetched from the database (real data, not mock data)

   - **Time Registrations Page (for admins/managers)**:
     - View all employees' clock-ins and clock-outs
     - Filter by user, date range, status
     - Edit functionality for time registrations
     - All data should be fetched from the database (real data, not mock data)
