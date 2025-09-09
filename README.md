
# Currency Price Ticker

A high-performance Symfony web application for tracking and providing cryptocurrency exchange rate data, featuring automatic updates every 5 minutes via the Binance API.

## Table of Contents

- [Installation and Setup](#installation-and-setup)
- [Technology Stack](#technology-stack)
- [Architectural Principles](#architectural-principles)
- [API Documentation](#api-documentation)
- [Binance API Integration](#binance-api-integration)
- [Testing](#testing)
- [Code Quality](#code-quality)
- [Development Workflow](#development-workflow)

## Installation and Setup

### Prerequisites

Before setting up the project, ensure you have the following installed on your local machine:

- **Docker** (latest stable version)
- **Docker Compose** (v2.0 or higher)
- **Make** (for simplified command execution)
- **Git** (for version control)

### Installation Instructions

1. **Clone the repository:**
   ```bash
   git clone <repository-url>
   cd currency-price-ticker
   ```

2. **Environment setup:**
   ```bash
   cp .env.example .env
   # Edit .env file with your configuration
   ```

3. **Build and start the application:**
   ```bash
   make up
   ```

   This command will:
   - Build all Docker containers
   - Start all services in the background
   - Run database migrations automatically

### Docker Compose Operations

- **Start all services:**
  ```bash
  docker-compose up -d --build
  ```

- **Stop all services:**
  ```bash
  docker-compose down
  ```

- **View service status:**
  ```bash
  docker-compose ps
  ```

- **Follow logs:**
  ```bash
  docker-compose logs -f
  ```

### Makefile Commands

The project includes a comprehensive Makefile for streamlined development operations:

| Command | Description | Example |
|---------|-------------|---------|
| `make up` | Build and start all services | `make up` |
| `make down` | Stop and remove all services | `make down` |
| `make restart` | Restart all services | `make restart` |
| `make logs` | Follow logs from all services | `make logs` |
| `make ps` | Show service status | `make ps` |
| `make shell` | Access app container shell | `make shell` |
| `make composer` | Run Composer commands | `make composer cmd="install"` |
| `make test` | Run PHPUnit tests | `make test` |
| `make stan` | Run PHPStan analysis | `make stan` |
| `make cs-fix` | Fix code style | `make cs-fix` |

### Service URLs

Once running, the application will be available at:

- **API Endpoint:** http://localhost:8080
- **API Documentation:** http://localhost:8080/api/doc
- **Database:** localhost:3310 (MySQL)
- **Redis:** localhost:6379

## Technology Stack

### Core Technologies

- **PHP**: 8.3 (with strict types enabled)
- **Symfony**: 7.3.* (latest stable)
- **MySQL**: 8.0 (primary database)
- **Redis**: Alpine (caching and message queue)
- **Nginx**: Alpine (web server)
- **Docker**: Multi-stage containerization

### Key Components

#### Backend Framework
- **Symfony Framework Bundle**: Core framework
- **Symfony Console**: CLI command management
- **Symfony Messenger**: Message queuing and async processing
- **Symfony Scheduler**: Automated task scheduling (every 5 minutes)
- **Symfony HTTP Client**: External API integration
- **Symfony Rate Limiter**: API rate limiting

#### Database & ORM
- **Doctrine ORM**: Object-relational mapping
- **Doctrine Migrations**: Database schema management
- **MySQL 8.0**: Time-series data storage with optimized indexing

#### API & Documentation
- **Nelmio API Doc Bundle**: OpenAPI/Swagger documentation
- **Symfony Serializer**: JSON response serialization
- **Symfony Validator**: Request validation

#### Monitoring & Logging
- **Monolog**: Structured logging
- **Sentry**: Error tracking and monitoring

### Technology Rationale

- **PHP 8.3**: Latest stable version with performance improvements and strict typing
- **Symfony 7.3**: Modern framework with excellent DDD support and robust architecture
- **MySQL 8.0**: Optimal for time-series data with advanced indexing capabilities
- **Redis**: High-performance caching and message queuing
- **Docker**: Consistent development and production environments

## Architectural Principles

### Architecture Overview

The application follows **Domain-Driven Design (DDD)** principles with a clean, layered architecture:

```
src/
├── Application/        # Application services, commands, queries
├── Domain/            # Business logic and domain entities
├── Infrastructure/    # External integrations and persistence
├── Controller/        # HTTP request handling
└── DTO/              # Data Transfer Objects
```

### Key Architectural Patterns

#### SOLID Principles
- **Single Responsibility**: Each class has one reason to change
- **Open/Closed**: Open for extension, closed for modification
- **Liskov Substitution**: Derived classes must be substitutable
- **Interface Segregation**: Clients shouldn't depend on unused interfaces
- **Dependency Inversion**: Depend on abstractions, not concretions

#### Domain-Driven Design
- **Entities**: `CurrencyRate` with identity and lifecycle
- **Value Objects**: `CurrencyPair`, `Money` for immutable values
- **Repositories**: Data access abstraction layer
- **Services**: Domain and application service layers

#### CQRS (Command Query Responsibility Segregation)
- **Commands**: `UpdateCurrencyRatesCommand` for write operations
- **Queries**: `GetLast24HoursRatesQuery`, `GetDailyRatesQuery` for reads
- **Handlers**: Separate handlers for each command/query

#### Event-Driven Architecture
- **Domain Events**: `CurrencyRateUpdatedEvent`, `CurrencyRateUpdateFailedEvent`
- **Message Bus**: Symfony Messenger for async processing

## API Documentation

### Purpose

The Currency Price Ticker API provides real-time and historical cryptocurrency exchange rate data for EUR-based trading pairs. It serves as a reliable data source for financial applications, trading platforms, and analytical tools.

### Base URL

```
http://localhost:8080/api
```

### Supported Currency Pairs

- `EUR/BTC` - Euro to Bitcoin
- `EUR/ETH` - Euro to Ethereum  
- `EUR/LTC` - Euro to Litecoin

### Core Endpoints

#### GET /api/rates/last-24h

Retrieves cryptocurrency exchange rates for the last 24 hours with 5-minute resolution.

**Parameters:**
- `pair` (required): Currency pair (`EUR/BTC`, `EUR/ETH`, `EUR/LTC`)

**Example Request:**
```bash
curl -X GET "http://localhost:8080/api/rates/last-24h?pair=EUR/BTC" \
  -H "Accept: application/json"
```

**Example Response:**
```json
{
  "data": [
    {
      "pair": "EUR/BTC",
      "rate": "91234.56",
      "timestamp": "2025-09-09T10:00:00+00:00"
    },
    {
      "pair": "EUR/BTC", 
      "rate": "91456.78",
      "timestamp": "2025-09-09T10:05:00+00:00"
    }
  ],
  "meta": {
    "pair": "EUR/BTC",
    "period": "24h",
    "count": 288,
    "start_time": "2025-09-08T10:00:00+00:00",
    "end_time": "2025-09-09T10:00:00+00:00"
  }
}
```

#### GET /api/rates/day

Retrieves cryptocurrency exchange rates for a specific date.

**Parameters:**
- `pair` (required): Currency pair (`EUR/BTC`, `EUR/ETH`, `EUR/LTC`)
- `date` (required): Date in YYYY-MM-DD format

**Example Request:**
```bash
curl -X GET "http://localhost:8080/api/rates/day?pair=EUR/BTC&date=2025-09-07" \
  -H "Accept: application/json"
```

### Rate Limiting

- **Standard API**: 100 requests per minute per IP
- **Rate limit headers** included in responses:
  ```
  X-RateLimit-Limit: 100
  X-RateLimit-Remaining: 99
  X-RateLimit-Reset: 1694252460
  ```

### OpenAPI Documentation

Interactive API documentation is available via Swagger UI:

**URL:** http://localhost:8080/api/doc

**OpenAPI Specification:** Available in the application at runtime

The specification includes:
- Complete endpoint documentation
- Request/response schemas
- Authentication requirements
- Error response formats

## Binance API Integration

### Integration Overview

The application integrates with the Binance public API to fetch real-time cryptocurrency exchange rates. The integration is designed for reliability, performance, and error resilience.

### Data Fetching Process

#### Supported Trading Pairs
The system fetches data for the following Binance trading pairs:
- `BTCEUR` (Bitcoin to Euro)
- `ETHEUR` (Ethereum to Euro) 
- `LTCEUR` (Litecoin to Euro)

#### Fetching Architecture

**Components:**
- `BinanceApiClient`: HTTP client for API communication
- `BinanceDataProvider`: Data transformation and validation
- `BinanceApiService`: Business logic orchestration

**Key Features:**
- **Retry Logic**: Exponential backoff with up to 3 retry attempts
- **Timeout Handling**: 10-second request timeout
- **Health Monitoring**: Built-in API health checks
- **Error Resilience**: Graceful handling of API failures

### Authentication

The Binance public API endpoints used by this application **do not require authentication**. The system uses:
- Public price ticker endpoints (`/api/v3/ticker/price`)
- Public health check endpoints (`/api/v3/ping`)

No API keys or authentication tokens are required for the current implementation.

### Data Processing

#### Data Flow
1. **Scheduler Trigger**: Symfony Scheduler runs every 5 minutes
2. **Command Dispatch**: `UpdateCurrencyRatesCommand` dispatched to message bus
3. **API Calls**: Parallel requests to Binance for all supported pairs
4. **Data Validation**: Response validation and transformation
5. **Persistence**: Storage in MySQL with timestamp indexing
6. **Event Publishing**: Domain events for successful/failed updates

#### Error Handling
- **Network Failures**: Automatic retry with exponential backoff
- **Invalid Responses**: Structured exception handling
- **Rate Limiting**: Built-in respect for Binance rate limits
- **Partial Failures**: Continue processing other pairs if one fails

#### Data Transformation
```php
// Raw Binance response
{
  "symbol": "BTCEUR",
  "price": "91234.56"
}

// Transformed to domain entity
CurrencyRate {
  pair: "EUR/BTC",
  rate: Money("91234.56", "EUR"),
  timestamp: DateTimeImmutable("2025-09-09T10:00:00+00:00")
}
```

## Testing

### Testing Strategy

The project implements a comprehensive testing strategy covering multiple testing levels:

#### Testing Levels
- **Unit Tests**: Individual class and method testing
- **Integration Tests**: Component interaction testing
- **API Tests**: HTTP endpoint testing

#### Testing Tools
- **PHPUnit**: 12.3.8+ (primary testing framework)
- **Symfony Test Framework**: Integration testing support
- **Doctrine Test Fixtures**: Database testing data

### Running Tests

#### Execute All Tests
```bash
# Using Docker Compose
docker-compose exec app vendor/bin/phpunit

# Using Composer script
docker-compose exec app composer test

# Using Make
make test
```

#### Run Specific Test Suites
```bash
# Run test
docker compose exec -e APP_ENV=test app php -d xdebug.mode=off bin/phpunit 

# OR 
docker-compose exec app vendor/bin/phpunit 
```

#### Test Configuration

**Configuration File:** `phpunit.dist.xml`

Key settings:
- **Strict mode**: Fails on deprecations, notices, and warnings
- **Code coverage**: Tracks coverage across `src/` directory
- **Test environment**: Isolated test database and configuration
- **Bootstrap**: Custom test bootstrap for dependency injection

#### Code Coverage

Generate code coverage reports:
```bash
# Generate HTML coverage report
docker-compose exec app vendor/bin/phpunit --coverage-html var/coverage

# Generate text coverage summary
docker-compose exec app vendor/bin/phpunit --coverage-text
```

Coverage reports are generated in `var/coverage/` directory.

### Test Examples

#### Unit Test Example
```php
class CurrencyPairTest extends TestCase
{
    public function testValidCurrencyPairCreation(): void
    {
        $pair = new CurrencyPair('EUR', 'BTC');
        
        $this->assertEquals('EUR', $pair->getBaseCurrency());
        $this->assertEquals('BTC', $pair->getQuoteCurrency());
        $this->assertEquals('EUR/BTC', $pair->toString());
    }
}
```

## Code Quality

### Static Analysis - PHPStan

PHPStan performs static analysis to catch potential bugs and ensure type safety.

**Configuration:** `phpstan.neon`
- **Analysis Level**: 8 (maximum strictness)
- **Coverage**: All files in `src/` directory
- **Extensions**: Symfony-specific rules

#### Running PHPStan
```bash
# Using Docker Compose
docker-compose exec app php -d xdebug.mode=off -d memory_limit=-1 vendor/bin/phpstan analyse src/ -c phpstan.neon

# Using Make
make stan
```

#### PHPStan Rules
- **Strict type checking**: No mixed types without justification
- **Dead code detection**: Unused methods and properties
- **Symfony integration**: Framework-specific validations
- **Doctrine validation**: ORM-specific checks

### Code Style - PHP CS Fixer

PHP CS Fixer automatically fixes code style issues according to PSR-12 standards.

**Configuration:** `.php-cs-fixer.dist.php`
- **Standard**: PSR-12 Extended Coding Style
- **Rules**: Additional formatting and organization rules
- **Scope**: All PHP files except `vendor/`, `var/`, `public/`

#### Running PHP CS Fixer
```bash
# Fix all code style issues
make cs-fix
docker-compose exec app vendor/bin/php-cs-fixer fix

# Check code style without fixing
docker-compose exec app composer cs-check
docker-compose exec app vendor/bin/php-cs-fixer fix --dry-run

# Fix specific file
docker-compose exec app vendor/bin/php-cs-fixer fix src/Controller/Api/CurrencyRateController.php
```

#### Code Style Rules
- **PSR-12 compliance**: Standard PHP coding style
- **Array syntax**: Short array syntax (`[]`)
- **Import ordering**: Alphabetical import sorting
- **Trailing commas**: In multiline arrays and function calls
- **Binary operators**: Proper spacing around operators
- **Method arguments**: Multiline formatting rules

### Quality Assurance Workflow

#### Pre-commit Quality Checks
Run the complete quality check suite:
```bash
# Run all quality checks
docker-compose exec app composer quality

# This runs:
# 1. composer cs-check   (Code style validation)
# 2. composer stan       (Static analysis)
# 3. composer test       (Unit tests)
```

#### Continuous Integration
The project is configured for CI/CD with automated quality gates:
1. **Code style validation** must pass
2. **PHPStan analysis** must be clean (level 8)
3. **All tests** must pass
4. **Code coverage** must meet minimum thresholds

#### Quality Standards
- **PHPStan Level**: 8 (maximum)
- **Code Coverage**: Target 80%+
- **PSR-12 Compliance**: 100%
- **Strict Types**: Required in all PHP files

## Development Workflow

### Getting Started

1. **Set up development environment:**
   ```bash
   make up
   ```

2. **Access the application shell:**
   ```bash
   make shell
   ```

3. **Run initial quality checks:**
   ```bash
   make test
   make stan
   make cs-fix
   ```

### Development Commands

#### Database Operations
```bash
# Run migrations
docker-compose exec app php bin/console doctrine:migrations:migrate --no-interaction

# Create new migration
docker-compose exec app php bin/console make:migration

# Validate schema
docker-compose exec app php bin/console doctrine:schema:validate
```

#### Cache Management
```bash
# Clear cache
docker-compose exec app php bin/console cache:clear

# Warm up cache
docker-compose exec app php bin/console cache:warmup
```

#### Manual executing tasks
```bash
docker-compose exec app php bin/console app:update-currency-rates

```
\
### Deployment

The application supports both development and production deployments through Docker multi-stage builds:

#### Development Deployment
```bash
make up
```

#### Production Deployment
```bash
# Build production image
docker build --target production -t currency-ticker:prod .

# Run production container
docker run -d \
  --name currency-ticker \
  -p 8080:80 \
  -e APP_ENV=prod \
  currency-ticker:prod
```




