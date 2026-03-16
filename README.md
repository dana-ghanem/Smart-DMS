# Smart Document Management System

A web-based Smart Document Management System with AI-powered search, built with Laravel and Python.

## Team
- Student A — Backend (Laravel)
- Student B — Authentication & AI
- Student C — Frontend & Database Design

## Requirements
- XAMPP (PHP 8.2+, MySQL)
- Composer
- Git
- VS Code

## Setup Instructions

### 1. Clone the project
git clone https://github.com/dana-ghanem/Smart-DMS.git
cd Smart-DMS

### 2. Install dependencies
composer install

### 3. Setup environment
copy .env.example .env
php artisan key:generate
php artisan migrate

### 4. Run the project
php artisan serve

Then open your browser at http://127.0.0.1:8000

## Project Structure
- app/Http/Controllers — Backend controllers
- app/Http/Controllers/Api — API controllers
- app/Services — Business logic
- app/Models — Database models
- database/migrations — Database tables
- routes/web.php — Web routes
- routes/api.php — API routes
- resources/views — Frontend pages