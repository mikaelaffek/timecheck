# Timetjek Frontend

This is the Vue 3 + TypeScript frontend for the Timetjek time tracking application.

## Requirements

- Node.js
- npm or yarn

## Setup

1. Install dependencies:
   ```
   npm install
   ```
   or
   ```
   yarn
   ```

2. Create a `.env` file with the API URL:
   ```
   VITE_API_URL=http://localhost:8000/api
   ```

3. Start the development server:
   ```
   npm run dev
   ```
   or
   ```
   yarn dev
   ```

4. Build for production:
   ```
   npm run build
   ```
   or
   ```
   yarn build
   ```

## Features

- User authentication (login/logout)
- Time registration (clock in/out)
- Edit time registrations
- View time reports
- Export reports to PDF/Excel
- Mobile responsive design

## Project Structure

- `src/assets`: Static assets
- `src/components`: Reusable Vue components
- `src/views`: Page components
- `src/router`: Vue Router configuration
- `src/stores`: Pinia stores for state management
- `src/services`: API services
- `src/types`: TypeScript interfaces and types
