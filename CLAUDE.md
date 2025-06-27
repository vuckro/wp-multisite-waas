# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Commands

### Build Commands
- `npm run build` - Full production build (runs composer install --no-dev, uglify, makepot, cleancss, archive)
- `npm run build:dev` - Development build (runs composer install, uglify, makepot, cleancss)
- `composer install` - Install PHP dependencies
- `composer install --no-dev` - Install only production PHP dependencies

### Asset Commands
- `npm run uglify` - Minify JavaScript files in assets/js/
- `npm run cleancss` - Minify CSS files in assets/css/
- `npm run makepot` - Generate translation POT file

### Quality Assurance
- `vendor/bin/phpcs` - Run PHP CodeSniffer (WordPress coding standards)
- `vendor/bin/phpstan` - Run PHPStan static analysis (level 0)
- `vendor/bin/phpunit` - Run PHPUnit tests

### Test Commands
- `vendor/bin/phpunit` - Run all PHPUnit tests
- `vendor/bin/phpunit tests/specific/TestClass.php` - Run specific test class

## Architecture Overview

WP Multisite WaaS is a WordPress Multisite plugin that transforms a network into a Website as a Service (WaaS) platform. The plugin follows a modular architecture:

### Core Structure
- **Main Plugin File**: `wp-multisite-waas.php` - Plugin bootstrap and metadata
- **Core Classes**: `inc/class-wp-ultimo.php` - Main plugin class and initialization

### Key Components

#### Models (`inc/models/`)
- Database entities with full CRUD operations
- Key models: Customer, Site, Membership, Payment, Product, Domain, Discount Code
- Each model extends `Base_Model` and implements appropriate interfaces

#### Database Layer (`inc/database/`)
- Custom database tables with schema management
- Query classes for complex database operations
- Meta tables for extensible data storage
- Engine classes provide base functionality for all database operations

#### Admin Pages (`inc/admin-pages/`)
- Network admin interface pages
- Customer-facing admin pages in `customer-panel/`
- List and edit pages for all major entities
- Base classes provide common functionality

#### Checkout System (`inc/checkout/`)
- Complete checkout flow with signup fields
- Modular signup field system in `signup-fields/`
- Field templates for different checkout styles
- Cart and line item management

#### Payment Gateways (`inc/gateways/`)
- Base gateway class with common functionality
- Stripe, PayPal, Manual, and Free gateway implementations
- Separate Stripe Checkout gateway for hosted payments

#### Limitations System (`inc/limitations/` and `inc/limits/`)
- Flexible system for limiting customer features
- Supports plugins, themes, disk space, users, post types, etc.
- Manager classes coordinate limitation enforcement

#### Domain Mapping (`inc/domain-mapping/`)
- Custom domain support with DNS verification
- Primary domain management
- SSO integration for cross-domain authentication

### Manager Classes (`inc/managers/`)
Central coordination classes that handle business logic:
- `Membership_Manager` - Subscription lifecycle management
- `Site_Manager` - Site creation and management
- `Gateway_Manager` - Payment processing coordination
- `Limitation_Manager` - Feature restriction enforcement

### Helper Classes (`inc/helpers/`)
Utility classes for common operations:
- `Site_Duplicator` - Template site cloning
- `Validator` - Input validation with custom rules
- `Screenshot` - Site preview generation

### Functions (`inc/functions/`)
Utility functions organized by domain:
- Model helpers (customer.php, site.php, membership.php, etc.)
- System helpers (form.php, url.php, string-helpers.php, etc.)
- Business logic helpers (financial.php, gateway.php, limitations.php)

## Development Notes

### Naming Conventions
- Function prefix: `wu_` for all plugin functions
- Class prefix: `WP_Ultimo` namespace or `wu_` for globals
- Custom capabilities: `wu_edit_*`, `wu_read_*`, `wu_delete_*`

### Database Tables
All custom tables are prefixed with `wu_` and use the BerlinDB framework for consistent database operations.

### Security
- Input sanitization using `wu_clean()` function
- WordPress nonces for form submissions
- Capability checks for all admin operations
- SQL injection prevention through prepared statements

### Testing
- PHPUnit tests in `tests/` directory
- Multisite testing enabled via `WP_TESTS_MULTISITE` constant
- Test bootstrap in `tests/bootstrap.php`
