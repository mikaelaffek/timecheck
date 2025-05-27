FROM node:20-alpine

# Set working directory
WORKDIR /app

# Copy package.json and package-lock.json
COPY package*.json ./

# Install dependencies with special handling for ARM64 architecture
RUN apk add --no-cache python3 make g++ \
    && npm install --platform=linux --arch=x64 \
    && npm rebuild \
    && npm cache clean --force

# Copy project files
COPY . .

# Expose port 3000
EXPOSE 3000

# Start development server
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
