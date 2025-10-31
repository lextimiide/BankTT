# Laravel Banking API

This is a Laravel-based banking API application with Docker support for easy deployment.

## Features

- User authentication and authorization
- Account management (create, block, unblock accounts)
- Transaction processing
- Swagger API documentation
- PostgreSQL database
- Docker containerization

## Local Development

### Prerequisites

- Docker and Docker Compose installed on your system

### Setup

1. Clone the repository
2. Copy `.env.example` to `.env` and configure your environment variables
3. Run the application:

```bash
docker compose up --build
```

The application will be available at `http://localhost:8000`

### API Documentation

Once the application is running, you can access the Swagger UI at:
`http://localhost:8000/api/documentation`

## Deployment on Render

### Prerequisites

- A Render account
- A PostgreSQL database instance on Render (or another cloud provider)

### Deployment Steps

1. **Create a PostgreSQL Database on Render:**
   - Go to your Render dashboard
   - Create a new PostgreSQL database
   - Note down the connection details (host, port, database name, username, password)

2. **Deploy the Application:**
   - In your Render dashboard, create a new "Web Service"
   - Connect your GitHub repository
   - Choose "Docker" as the runtime
   - Set the following environment variables in Render:
     ```
     APP_ENV=production
     APP_DEBUG=false
     APP_KEY=<your-app-key>  # Generate with php artisan key:generate
     DB_CONNECTION=pgsql
     DB_HOST=<your-postgres-host>
     DB_PORT=5432
     DB_DATABASE=<your-database-name>
     DB_USERNAME=<your-username>
     DB_PASSWORD=<your-password>
     ```
   - Set the build command to: `docker build -t myapp .`
   - Set the start command to: `docker run -p $PORT:8000 myapp`

3. **Database Migration:**
   - After deployment, you may need to run migrations manually
   - You can do this by connecting to your Render service via SSH or by adding a migration command to your deployment script

4. **Access the Application:**
   - Your API will be available at the URL provided by Render
   - API documentation: `https://your-render-url.com/api/documentation`

### Environment Variables for Render

Make sure to set these environment variables in your Render service:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_KEY` (generate a new one for production)
- Database connection details as shown above
- `PORT` (automatically set by Render, used in nginx.conf)

### Notes

- The application uses PostgreSQL as the database
- Swagger documentation is automatically generated
- The Docker setup includes both the Laravel app and PostgreSQL for local development
- For production, use an external PostgreSQL instance (like Render's managed database)

## API Endpoints

The API provides endpoints for:
- Authentication (login/register)
- Account management
- Transactions
- Admin operations

Refer to the Swagger documentation for detailed API specifications.
