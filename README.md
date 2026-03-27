# Task and Workspace Management API

A professional, RESTful API built with Laravel 13 designed for hierarchical workspace organization and task management. This project emphasizes clean architecture, standardized responses, and robust validation.

## Features

- **Authentication**: Secure user registration and session-based authentication using Laravel Sanctum.
- **Workspaces**:
  - Support for nested hierarchies (up to 3 levels deep).
  - Archive and restore functionality with soft delete support.
  - Global search with hierarchical path resolution.
  - Breadcrumb generation for deep navigation.
- **Task Management**:
  - Root tasks and sub-tasks organization.
  - Due date and priority level tracking.
  - Task movement between parent nodes and workspaces.
  - Status updates (Todo, In Progress, Done).
- **Architecture**:
  - Strict Service-Repository pattern implementation.
  - Standardized JSON API response helper for consistency.
  - Form Request validation for all incoming data.
- **Code Quality**:
  - Fully tested with Pest PHP.
  - Strict type hints and modern PHP 8 features.
  - Code formatting enforced by Laravel Pint.

## Technology Stack

- PHP 8.3/8.4
- Laravel 13
- Laravel Sanctum
- Pest PHP (Testing Framework)
- SQLite (Database)

## Getting Started

### Prerequisites

- PHP 8.3 or higher
- Composer
- Node.js & NPM
- Supported database engine (MySQL, PostgreSQL, SQLite, etc.)

### Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/yogaprastyoo/task.git
   cd task
   ```

2. Install PHP and JavaScript dependencies:
   ```bash
   composer install
   npm install
   ```

3. Initialize the environment:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configure your database in the `.env` file and run migrations:
   ```bash
   php artisan migrate --seed
   ```

5. Compile assets and start the development server:
   ```bash
   npm run build
   php artisan serve
   ```

## API Documentation

### Authentication
- `POST /api/auth/register`: Register a new account.
- `POST /api/auth/login`: Authenticate and start a session.

### Workspaces
- `GET /api/workspaces`: List workspaces (supports filtering/search).
- `GET /api/workspaces/root`: Retrieve top-level workspaces.
- `GET /api/workspaces/{workspace}`: Show detailed workspace info.
- `GET /api/workspaces/{workspace}/breadcrumbs`: Get full path to workspace.
- `PATCH /api/workspaces/{workspace}/move`: Change workspace parent.

### Tasks
- `GET /api/workspaces/{workspace}/tasks`: List root tasks in a specific workspace.
- `POST /api/workspaces/{workspace}/tasks`: Create a new task.
- `PATCH /api/tasks/{task}/status`: Update task completion status.
- `POST /api/tasks/{task}/subtasks`: Create a child task.

## Testing

Run the test suite using Pest:
```bash
php artisan test
```

## License

This project is open-source software licensed under the [MIT license](https://opensource.org/licenses/MIT).
