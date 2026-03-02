# Contributing to ZenithSoles

Dhanyavaad! Aap is project mein contribute karna chahte hain — yahan kuch asaan rules hain jo follow karein.

## Quick start
- Fork the repo and create a branch: `feature/your-feature` or `fix/issue-number`
- Follow commit message style: `type(scope): short description` (e.g. `feat(auth): add login throttling`)

## Branching
- Main branches: `main` (production-ready), `develop` (integration)
- Feature branches: `feature/…`
- Bugfix branches: `fix/…` or `hotfix/…` for urgent fixes

## Pull Request (PR) process
- Open PR against `develop` (or `main` only for hotfixes)
- Fill the PR description with what changed and why
- Include screenshots or logs if relevant

### PR checklist (required)
- [ ] Code follows repository style and passes linter
- [ ] Tests added or updated for new behaviour
- [ ] Documentation / CHANGELOG updated if public API changed
- [ ] No secrets or env values committed

## Coding style
- Use PSR-12 for PHP code
- Keep methods small and single-responsibility

## How to run locally (example, Windows PowerShell)
```powershell
# Install dependencies (if composer.json present)
composer install
# Copy env example
copy .env.example .env
# Run migrations (if Laravel is present)
php artisan migrate
```

If you need help, open an issue describing what you want to work on.
