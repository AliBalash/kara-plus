# Contributing

This project is still evolving. Contributions should stay focused, small, and easy to review.

## Recommended Workflow

1. Create a dedicated branch for each change.
2. Keep infrastructure, UI, and domain changes separated when possible.
3. Update documentation for any setup or behavior changes.
4. Run the relevant local checks before submitting changes:

```bash
php artisan test
npm run build
```

## Security and Secrets

- Do not commit real `.env` values
- Use `.env.example` for safe placeholders only
- Avoid committing generated build artifacts unless they are intentionally versioned
