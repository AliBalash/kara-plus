# Kara Plus

Kara Plus is an early-stage Laravel and Livewire admin panel for car rental operations. The repository currently provides the application foundation, admin layout shell, and dashboard entry point that can be extended into reservation, fleet, customer, and reporting workflows.

## Project Status

This repository is best understood as an implementation scaffold rather than a finished product. The current codebase is valuable as a portfolio artifact because it shows a clean Laravel 11 setup, Livewire-based panel composition, and a practical direction for a Persian RTL back-office application.

## Highlights

- Laravel 11 application structure
- Livewire-based admin panel composition
- RTL-ready layout for Persian interfaces
- Vite, Tailwind CSS, and modern frontend asset pipeline
- PHPUnit test scaffold included
- Clean separation between layout components and page-level Livewire classes

## Tech Stack

- PHP 8.2+
- Laravel 11
- Livewire 3
- Vite
- Tailwind CSS
- PHPUnit

## Structure

- `app/Livewire/Pages/Panel/Admin`: page-level Livewire components
- `app/Livewire/Components/Panel`: reusable admin layout components
- `resources/views/layouts`: application layouts
- `resources/views/livewire`: Livewire blade views
- `routes/web.php`: web routes, including the admin dashboard route
- `tests`: application test scaffold

## Current Features

- Admin dashboard route at `/admin/dashboard`
- Reusable panel shell with header, aside, and footer components
- RTL layout support for Persian-language interfaces
- Laravel configuration and test scaffolding ready for expansion

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js
- npm

### Setup

1. Clone the repository.
2. Install PHP dependencies:

```bash
composer install
```

3. Install frontend dependencies:

```bash
npm install
```

4. Copy the environment file:

```bash
cp .env.example .env
```

5. Generate the Laravel application key:

```bash
php artisan key:generate
```

6. Start the local development environment:

```bash
composer run dev
```

7. Open the current dashboard route:

```text
http://localhost:8000/admin/dashboard
```

## Environment Variables

This repository already includes `.env.example`. Review and update the following values for your local setup:

- `APP_NAME`
- `APP_URL`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `MAIL_*`
- `REDIS_*`

## Development

Useful commands:

```bash
composer run dev
php artisan test
npm run dev
npm run build
```

## Testing

Run the Laravel test suite with:

```bash
php artisan test
```

Current automated coverage is scaffold-level and should be expanded as business modules are implemented.

## Roadmap

- Add authentication and role-based access control
- Implement rental, reservation, and fleet management modules
- Add reporting widgets backed by real domain data
- Introduce feature tests for the admin workflows

## Screenshots / Demo

Screenshots or a short demo video can be added here once the admin workflows are further implemented.

## License

No license has been specified yet.

## Author

Developed by Ali Balash.
