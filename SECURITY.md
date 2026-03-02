# Security Policy

If you discover a security vulnerability, please report it privately so we can respond and fix it before public disclosure.

Preferred contact: security@your-domain.example (replace with real email)

Steps for reporters:
1. Send an email with reproducible steps and severity.
2. Provide any PoC code or logs privately.
3. We will acknowledge within 48 hours and provide an estimated timeline.

If you accidentally commit secrets (API keys, DB passwords):
1. Immediately rotate/revoke the secret.
2. Create an incident issue and notify maintainers.
3. Use `git filter-repo` or `bfg` to remove secrets from history (if needed).

Secrets must never be committed. Use `.env` (gitignored) and secret managers for CI.
