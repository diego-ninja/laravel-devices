#!/bin/bash

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
YELLOW='\033[1;33m'
NC='\033[0m'

# Package directory (where the script is executed)
PACKAGE_DIR="$PWD"
DEV_ENV_DIR="$PACKAGE_DIR/.dev-environment"

# Check if jq is installed (for JSON manipulation)
if ! command -v jq &> /dev/null; then
    echo -e "${YELLOW}Warning: jq is not installed. JSON manipulation will be limited.${NC}"
fi

# Check if we're in the package directory
if [ ! -f "composer.json" ] || ! grep -q "diego-ninja/laravel-devices" "composer.json"; then
    echo -e "${RED}Error: This script must be run from the laravel-devices package root directory${NC}"
    exit 1
fi

# Check if docker is installed
if ! command -v docker &> /dev/null; then
    echo -e "${RED}Error: Docker is not installed. Please install it first.${NC}"
    exit 1
fi

# Create or clean development directory
if [ -d "$DEV_ENV_DIR" ]; then
    echo -e "${YELLOW}Existing development environment detected.${NC}"
    read -p "Do you want to remove it and create a new one? (y/n) " -n 1 -r
    echo
    if [[ $REPLY =~ ^[Yy]$ ]]; then
        echo -e "${BLUE}Removing previous environment...${NC}"
        if [ -f "$DEV_ENV_DIR/vendor/bin/sail" ]; then
            (cd "$DEV_ENV_DIR" && ./vendor/bin/sail down -v)
        fi
        rm -rf "$DEV_ENV_DIR"
    else
        echo -e "${YELLOW}Operation cancelled.${NC}"
        exit 1
    fi
fi

# Function to check if a service is ready
function wait_for_service() {
    local service=$1
    local max_attempts=$2
    local attempt=1
    local delay=2

    echo -ne "${BLUE}Waiting for $service..."
    while [ $attempt -le $max_attempts ]; do
        case $service in
            mysql)
                if docker compose exec mysql mysqladmin ping -h"localhost" -u"${DB_USERNAME}" -p"${DB_PASSWORD}" >/dev/null 2>&1; then
                    echo -e "${GREEN}ready!${NC}"
                    return 0
                fi
                ;;
            redis)
                if docker compose exec redis redis-cli ping >/dev/null 2>&1; then
                    echo -e "${GREEN}ready!${NC}"
                    return 0
                fi
                ;;
            laravel)
                if curl -s "http://localhost:${APP_PORT:-8000}/health" >/dev/null 2>&1; then
                    echo -e "${GREEN}ready!${NC}"
                    return 0
                fi
                ;;
        esac
        echo -n "."
        sleep $delay
        ((attempt++))
    done

    echo -e "\n${RED}Error: $service did not respond after $max_attempts attempts${NC}"
    return 1
}

# Create development directory
echo -e "${BLUE}Creating new development environment...${NC}"
mkdir -p "$DEV_ENV_DIR"
cd "$DEV_ENV_DIR"

# Create new Laravel application using curl
echo -e "${BLUE}Creating new Laravel application...${NC}"
curl -s "https://laravel.build/laravel" | bash

# Move everything to .dev-environment
echo -e "${BLUE}Organizing files...${NC}"
mv laravel/* laravel/.[!.]* .
rmdir laravel

# Update package .gitignore if needed
if ! grep -q "^.dev-environment/" "$PACKAGE_DIR/.gitignore"; then
    echo -e "${BLUE}Updating package .gitignore...${NC}"
    echo ".dev-environment/" >> "$PACKAGE_DIR/.gitignore"
fi

# Copy package .env.example to development environment if exists
if [ -f "$PACKAGE_DIR/.env.example" ]; then
    echo -e "${BLUE}Copying package environment configuration...${NC}"
    cp "$PACKAGE_DIR/.env.example" .env

    # Update development specific variables
    sed -i '' 's#APP_URL=.*#APP_URL=http://localhost:8000#g' .env
    sed -i '' 's#DB_HOST=.*#DB_HOST=mysql#g' .env
    sed -i '' 's#REDIS_HOST=.*#REDIS_HOST=redis#g' .env
    sed -i '' 's#MAIL_HOST=.*#MAIL_HOST=mailpit#g' .env
else
    echo -e "${YELLOW}Warning: No .env.example found in package root. Using Laravel defaults.${NC}"
fi

echo -e "${BLUE}Tweaking Laravel Sail...${NC}"
cat > docker-compose.yml << 'EOL'
services:
    laravel.test:
        build:
            context: ./docker/8.3
            dockerfile: Dockerfile
            args:
                WWWGROUP: '${WWWGROUP}'
        image: sail-8.3/app
        extra_hosts:
            - 'host.docker.internal:host-gateway'
        ports:
            - '${APP_PORT:-8000}:80'
            - '${VITE_PORT:-5173}:${VITE_PORT:-5173}'
        environment:
            WWWUSER: '${WWWUSER}'
            LARAVEL_SAIL: 1
            XDEBUG_MODE: '${SAIL_XDEBUG_MODE:-off}'
            XDEBUG_CONFIG: '${SAIL_XDEBUG_CONFIG:-client_host=host.docker.internal}'
        volumes:
            - '.:/var/www/html'
            - '${PACKAGE_DIR:-../}:/var/www/package'
        networks:
            - sail
        depends_on:
            - mysql
            - redis
            - mailpit
    mysql:
        image: 'mysql/mysql-server:8.0'
        ports:
            - '${FORWARD_DB_PORT:-3306}:3306'
        environment:
            MYSQL_ROOT_PASSWORD: '${DB_PASSWORD}'
            MYSQL_ROOT_HOST: '%'
            MYSQL_DATABASE: '${DB_DATABASE}'
            MYSQL_USER: '${DB_USERNAME}'
            MYSQL_PASSWORD: '${DB_PASSWORD}'
            MYSQL_ALLOW_EMPTY_PASSWORD: 1
        volumes:
            - 'sail-mysql:/var/lib/mysql'
        networks:
            - sail
        healthcheck:
            test: ['CMD', 'mysqladmin', 'ping', '-p${DB_PASSWORD}']
            retries: 3
            timeout: 5s
    redis:
        image: 'redis:alpine'
        ports:
            - '${FORWARD_REDIS_PORT:-6379}:6379'
        volumes:
            - 'sail-redis:/data'
        networks:
            - sail
        healthcheck:
            test: ['CMD', 'redis-cli', 'ping']
            retries: 3
            timeout: 5s
    mailpit:
        image: 'axllent/mailpit:latest'
        ports:
            - '${FORWARD_MAILPIT_PORT:-1025}:1025'
            - '${FORWARD_MAILPIT_DASHBOARD_PORT:-8025}:8025'
        networks:
            - sail
networks:
    sail:
        driver: bridge
volumes:
    sail-mysql:
        driver: local
    sail-redis:
        driver: local
EOL

# Update composer.json
echo -e "${BLUE}Configuring composer.json...${NC}"
cat > "composer.json" << 'EOL'
{
    "name": "laravel/laravel",
    "type": "project",
    "description": "The skeleton application for the Laravel framework.",
    "keywords": ["laravel", "framework"],
    "license": "MIT",
    "require": {
        "php": "^8.2",
        "ext-pdo": "*",
        "ext-redis": "*",
        "diego-ninja/laravel-devices": "*",
        "geoip2/geoip2": "^3.0",
        "laravel/framework": "^11.9",
        "laravel/jetstream": "^5.3",
        "laravel/octane": "^2.5",
        "laravel/pulse": "*",
        "laravel/sanctum": "^4.0",
        "laravel/tinker": "^2.9",
        "livewire/livewire": "^3.0",
        "blade-ui-kit/blade-ui-kit": "^0.6.3",
        "blade-ui-kit/blade-icons": "^1.7",
        "outhebox/blade-flags": "^1.5"
    },
    "require-dev": {
        "fakerphp/faker": "^1.24",
        "laravel/sail": "^1",
        "mockery/mockery": "^1.6",
        "nunomaduro/collision": "^8.1",
        "phpunit/phpunit": "^11",
        "spatie/laravel-ignition": "^2.4"
    },
    "repositories": [
        {
            "type": "path",
            "url": "/var/www/package",
            "options": {
                "symlink": true
            }
        }
    ],
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi"
        ]
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        }
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true,
            "php-http/discovery": true
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true
}
EOL

# Start Sail and wait for services
echo -e "${BLUE}Starting development environment...${NC}"
./vendor/bin/sail up -d

echo -e "${BLUE}Waiting for services to be ready...${NC}"
wait_for_service "mysql" 30 || exit 1
wait_for_service "redis" 30 || exit 1

# Install Jetstream
echo -e "${BLUE}Installing Jetstream...${NC}"
./vendor/bin/sail composer require laravel/jetstream
./vendor/bin/sail artisan jetstream:install livewire --teams

# Install and configure the package
echo -e "${BLUE}Installing laravel-devices...${NC}"
./vendor/bin/sail composer require "diego-ninja/laravel-devices:*"

# Publish assets
echo -e "${BLUE}Publishing package assets...${NC}"
./vendor/bin/sail artisan vendor:publish --provider="Ninja\DeviceTracker\DeviceTrackerServiceProvider"

# Configure seeders
echo -e "${BLUE}Configuring seeders...${NC}"
mkdir -p database/seeders

# Create DatabaseSeeder
cat > database/seeders/DatabaseSeeder.php << 'EOL'
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Ninja\DeviceTracker\Database\Seeders\DevicesSeeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create default test user
        \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Create additional users
        \App\Models\User::factory(10)->create();

        // Run Device Tracker seeders
        $this->call(DevicesSeeder::class);
    }
}
EOL

# Create User factory
mkdir -p database/factories
cat > database/factories/UserFactory.php << 'EOL'
<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
EOL

# Build frontend assets
echo -e "${BLUE}Building frontend assets...${NC}"
./vendor/bin/sail npm install
./vendor/bin/sail npm run build

# Run migrations and seeders
echo -e "${BLUE}Running migrations and seeders...${NC}"
./vendor/bin/sail artisan migrate:fresh --seed

# Create health check endpoint for startup detection
cat > routes/web.php << 'EOL'
<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/health', function () {
    return response('OK', 200);
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
});
EOL

echo -e "${GREEN}Installation completed successfully!${NC}"
echo -e "${BLUE}Development environment is ready. Use the following commands:${NC}"
echo -e "  ${GREEN}./dev up${NC}      - Start the environment"
echo -e "  ${GREEN}./dev stop${NC}    - Stop the environment"
echo -e "  ${GREEN}./dev shell${NC}   - Access the container shell"
echo -e ""
echo -e "Default test user:"
echo -e "  Email: ${GREEN}test@example.com${NC}"
echo -e "  Password: ${GREEN}password${NC}"
echo -e ""
echo -e "The application is available at: ${GREEN}http://localhost:8000${NC}"
echo -e "Mailpit is available at: ${GREEN}http://localhost:8025${NC}"