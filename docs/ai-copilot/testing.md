# Testing

Run `php artisan test --filter=Ai` for the focused suite, then `php artisan test` and `npm run build`. External providers are never contacted by Laravel tests; the Ajil HTTP boundary is faked.
