#!/bin/bash

# Colors for terminal output
YELLOW="\033[1;33m"
GREEN="\033[0;32m"
RED="\033[0;31m"
NC="\033[0m" # No Color

# Check if containers are already running
if [ "$(docker ps -q -f name=timetjek)" ]; then
    echo -e "${YELLOW}Containers are already running. Stopping them first...${NC}"
    docker-compose down
fi

# Start the containers
echo -e "${YELLOW}Starting Docker containers...${NC}"
docker-compose up -d

# Wait for the database to be ready
echo -e "${YELLOW}Waiting for database to be ready...${NC}"
while ! docker-compose exec db mysqladmin ping -h localhost -u root -proot --silent; do
    echo -e "${YELLOW}Still waiting for database...${NC}"
    sleep 2
done

echo -e "${GREEN}Database is ready!${NC}"

# Wait for the backend to be ready
echo -e "${YELLOW}Waiting for backend to be ready...${NC}"
MAX_RETRIES=30
RETRIES=0

while [ $RETRIES -lt $MAX_RETRIES ]; do
    if curl -s http://localhost:8000 > /dev/null; then
        echo -e "${GREEN}Backend is ready!${NC}"
        break
    else
        echo -e "${YELLOW}Still waiting for backend...${NC}"
        RETRIES=$((RETRIES+1))
        sleep 5
    fi
done

if [ $RETRIES -eq $MAX_RETRIES ]; then
    echo -e "${RED}Backend did not become ready in time. Please check logs with: docker-compose logs backend${NC}"
    exit 1
fi

# Create Laravel directories if they don't exist
echo -e "${YELLOW}Ensuring Laravel directory structure...${NC}"
docker-compose exec backend mkdir -p /var/www/html/bootstrap/cache
docker-compose exec backend mkdir -p /var/www/html/storage/framework/sessions
docker-compose exec backend mkdir -p /var/www/html/storage/framework/views
docker-compose exec backend mkdir -p /var/www/html/storage/framework/cache
docker-compose exec backend mkdir -p /var/www/html/storage/logs
docker-compose exec backend chmod -R 777 /var/www/html/storage
docker-compose exec backend chmod -R 777 /var/www/html/bootstrap/cache
# Run migrations and seeders
echo -e "${YELLOW}Running database migrations...${NC}"
docker-compose exec backend php artisan migrate --force

echo -e "${YELLOW}Running database seeders...${NC}"
docker-compose exec backend php artisan db:seed --force

echo -e "${GREEN}Timetjek is now running!${NC}"
echo -e "${GREEN}Backend: http://localhost:8000${NC}"
echo -e "${GREEN}Frontend: http://localhost:3000${NC}"
echo -e "${YELLOW}Default admin login:${NC}"
echo -e "Personal ID: ADMIN001"
echo -e "Password: password"

# Open browser automatically if on Mac
if [[ "$(uname)" == "Darwin" ]]; then
  echo -e "${YELLOW}Opening browser...${NC}"
  open http://localhost:3000
fi
