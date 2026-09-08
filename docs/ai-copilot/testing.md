# Testing

Run `php artisan test --filter=Ai` for the focused suite, then `php artisan test` and `npm run build`. External providers are never contacted by Laravel tests; the Ajil HTTP boundary is faked.

After deployment, run `php artisan ai:health` inside Laravel. It validates the private gateway and catalog without sending a model prompt or printing any credential.
