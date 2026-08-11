# ProfitLens Integrations

Tenant API docs are available at `/api/v1/openapi.json`.

- Plaid: create a link token, exchange the public token, then import transactions.
- Webhooks: subscribe to `sale.recorded`, `sale.big`, and `expense.logged`.
- Zapier: use `public/zapier-app.json` as the app definition starting point.
- Browser extension: load `public/browser-extension` as an unpacked Chrome extension.
- Slack/Discord: point slash commands to `/api/v1/integrations/slack/command` or `/api/v1/integrations/discord/command`.
- Google Sheets: export rows from `/api/v1/integrations/google-sheets/export` and post edited expense rows back to `/api/v1/integrations/google-sheets/import`.
