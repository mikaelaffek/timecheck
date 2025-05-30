FROM node:20-alpine

# Set working directory
WORKDIR /app

# Set environment variables to skip native modules
ENV ROLLUP_SKIP_NODEJS=true
ENV VITE_SKIP_NATIVE=true
ENV ESBUILD_BINARY_PATH=esbuild

# Copy package.json and package-lock.json
COPY package*.json ./

# Install dependencies with special handling for ARM64 architecture
RUN apk add --no-cache python3 make g++ \
    && npm config set legacy-peer-deps true \
    && npm install --no-optional \
    && npm cache clean --force

# Copy project files
COPY . .

# Expose port 3000
EXPOSE 3000

# Start development server
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]
