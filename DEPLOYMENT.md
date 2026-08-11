# Free deployment

## Recommended demo stack

- Web: Render Free Web Service using `render.yaml`
- Database: Neon Free Postgres, with one schema per tenant
- DNS/TLS: a domain with root and wildcard DNS records

This stack is appropriate for a portfolio, beta, or low-traffic demo—not for
production financial records. Free services sleep and their filesystems are ephemeral.

## Setup

1. Push the repository to GitHub or GitLab. Never commit `.env`, SQLite databases,
   receipts, API keys, or customer data.
2. Create a Neon project and copy its direct Postgres connection string.
3. In Render, create a Blueprint from the repository.
4. Set `DATABASE_URL`, `APP_URL`, `TENANT_DOMAIN`, and `CENTRAL_DOMAINS` when prompted.
5. Add the central hostname as a Render custom domain.
6. Point both the central hostname and its wildcard to Render, and verify TLS.
7. Deploy. The container runs central migrations and optimization before startup.

A provider hostname alone cannot serve tenant URLs such as `customer.app.example.com`;
the full product needs wildcard DNS.

## Before real customers

- Move receipt uploads to an S3-compatible object store.
- Configure HTTP-based transactional mail; free Render blocks common SMTP ports.
- Configure Stripe before advertising payments. Without keys, billing is dev mode.
- Add a durable queue worker, error monitoring, backups, legal pages, and data policies.

## Smoke test

Check `/up`, load the HTTPS landing page, create a test workspace, confirm its
subdomain resolves, then create a customer, product, sale, expense, and invoice.
Redeploy once and verify the Postgres records remain.
