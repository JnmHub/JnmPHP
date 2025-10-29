# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

JnmPHP is a lightweight, modern PHP framework designed for building high-performance APIs and web applications. It leverages PHP 8+ Attributes and combines Laravel's core components (Eloquent ORM, Blade templates, Validation, Container) with modern PHP features.

## Development Commands

### Setup and Installation
```bash
# Install dependencies
composer install

# Create environment file
cp .env.example .env

# Configure database connection in .env
# DB_HOST=127.0.0.1
# DB_DATABASE=your_database
# DB_USERNAME=your_username
# DB_PASSWORD=your_password
```

### Running the Application
```bash
# Start the development server (PHP built-in server)
php -S localhost:8000

# Or use your preferred web server pointing to the project root
# The entry point is index.php
```

### Cache Management
The framework uses caching for routes, views, and subscribers. Cache files are stored in the `cache/` directory. Clear cache if you make changes to:
- Route attributes in controllers
- View files
- Event subscribers

## Architecture Overview

### Key Design Principles
- **Configuration as Code**: Uses PHP Attributes instead of configuration files
- **Service Provider Pattern**: Core services are registered via providers in `config/providers.php`
- **Attribute-Driven**: Routes, model relationships, validation rules, and middleware are defined using PHP 8 Attributes

### Core Components

#### Application Structure
- `index.php` - Application entry point
- `app/` - Application code (controllers, models, middleware, providers)
- `kernel/` - Framework core code
- `config/` - Configuration files
- `cache/` - Framework cache (routes, views, subscribers)
- `lang/` - Multi-language files

#### Service Providers
The application uses service providers defined in `config/providers.php`:
- `AppServiceProvider` - Core application services
- `DatabaseServiceProvider` - Eloquent ORM initialization
- `EventServiceProvider` - Event system and subscriber registration
- `RouteServiceProvider` - Route registration and caching
- `ViewServiceProvider` - Blade template engine

#### Attribute System
The framework's core innovation is using PHP Attributes for:

**Routes** (`app/Controller/`):
- `#[RoutePrefix('/path')]` - Controller-level route prefix
- `#[Get('/path)]`, `#[Post('/path)]` - HTTP method routes
- `#[PathVariable('name')]` - URL parameter injection
- `#[RequestBody]` - JSON request body binding
- `#[Middleware('alias')]` - Middleware assignment

**Models** (`app/Models/`):
- `#[TableField]` - Database field configuration
- `#[HasMany]`, `#[BelongsTo]`, `#[BelongsToMany]` - Relationships
- `#[Validate]` - Validation rules
- `#[Accessor]`, `#[Mutator]` - Property accessors/mutators

#### Database Layer
- Base model: `Kernel\Database\BaseModel` (extends Eloquent Model)
- Uses Laravel's Eloquent ORM with attribute-based configuration
- Models automatically handle field mapping, relationships, and validation
- Supports automatic request validation and model binding

#### Request/Response Flow
1. Request enters through `index.php`
2. Environment variables loaded via Dotenv
3. Application singleton initialized
4. Service providers registered and booted
5. Router dispatches request with middleware pipeline
6. Controller method executed with automatic parameter injection
7. Response automatically formatted (JSON or View)

### Key Directories

#### Kernel (`kernel/`)
- `Attribute/` - All PHP Attribute definitions
- `Database/` - Database extensions (BaseModel and Traits)
- `Events/` - Event management system
- `Middleware/` - Middleware core (Pipeline)
- `Request/` - HTTP request handling
- `Response/` - Response handling (JsonResponse, ViewResponse)
- `Routing/` - Router and route collection
- `Validation/` - Validator factory

#### Application (`app/`)
- `Controller/` - HTTP controllers with route attributes
- `Models/` - Eloquent models with database attributes
- `Middleware/` - Custom middleware classes
- `Providers/` - Service providers
- `Subscribers/` - Event subscribers
- `View/` - Blade template files

## Development Guidelines

### Creating Controllers
- Extend `BaseController` for view and file response helpers
- Use route attributes instead of separate route files
- Leverage automatic request validation with `#[RequestBody]`

### Creating Models
- Extend `BaseModel` instead of Eloquent's Model
- Use `#[TableField]` for field configuration
- Define relationships with relationship attributes
- Add validation rules directly to properties with `#[Validate]`

### Middleware
- Implement `MiddlewareInterface`
- Register aliases in `MiddlewareManager`
- Apply using `#[Middleware('alias')]` attribute

### Testing
- No specific test runner configured - use PHP's built-in testing or add PHPUnit if needed
- Framework is lightweight and suitable for integration testing

## Important Notes

- Framework requires PHP 8.2+
- All configurations are done through PHP Attributes
- The framework follows a singleton pattern for the Application instance
- Middleware, routes, views, and subscribers are cached for performance
- Use the existing cache structure when extending the framework