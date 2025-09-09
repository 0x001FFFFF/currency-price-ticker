# Makefile for Currency Price Ticker Development

.PHONY: help up down restart logs ps shell composer test stan cs-fix

# Default command displays help
help:
	@echo "Usage: make [command]"
	@echo ""
	@echo "Available commands:"
	@echo "  up           Build and start all services in the background"
	@echo "  down         Stop and remove all services"
	@echo "  restart      Restart all services"
	@echo "  logs         Follow logs from all services"
	@echo "  ps           Show the status of all services"
	@echo "  shell        Get a shell into the PHP application container"
	@echo "  composer     Run a Composer command (e.g., make composer cmd=\"install\")"
	@echo "  test      	  Run PHPUnit tests"
	@echo "  stan         Run PHPStan static analysis"
	@echo "  cs-fix       Run PHP CS Fixer to fix code style"
	@echo "  post-install Run post install scripts"

up:
	@echo "🚀 Starting up the development environment..."
	docker compose up -d --build
	sleep 3
	docker compose exec app php bin/console doctrine:migrations:migrate --no-interaction

down:
	@echo "🛑 Shutting down the development environment..."
	docker compose down

restart: down up

logs:
	@echo "🔍 Tailing logs..."
	docker compose logs -f

ps:
	@echo "📊 Services status:"
	docker compose ps

shell:
	@echo "💻 Accessing the app container shell..."
	docker compose exec app sh

# Allows running any composer command, e.g., `make composer cmd="require symfony/mailer"`
composer:
	@echo "🎵 Running Composer command: $(cmd)..."
	docker compose exec app composer $(cmd)

test:
	@echo "🧪 Running PHPUnit tests..."
	docker compose exec app vendor/bin/phpunit

stan:
	@echo "🔬 Running PHPStan analysis..."
	docker compose exec app vendor/bin/phpstan analyse src

cs-fix:
	@echo "🎨 Fixing code style with PHP-CS-Fixer..."
	docker compose exec app vendor/bin/php-cs-fixer fix

post-install:
	@echo "🔧 Running post-install scripts in app..."
	docker compose exec app composer run-script post-install-cmd
	@echo "🔧 Running post-install scripts in scheduler..."
	docker compose exec scheduler composer run-script post-install-cmd

up-post: up post-install
