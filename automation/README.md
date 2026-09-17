# TravelMatch N8N automation

Three importable N8N workflows, each **Webhook → Format Email (Code node) →
Send Email**, all sharing whatever SMTP credential you set up (a Mailtrap
inbox is the easiest way to test this without sending real email).

| Workflow file | Triggered by | Purpose |
| --- | --- | --- |
| `n8n-workflow.json` | `POST /api/notify` | Emails a user their quiz results |
| `n8n-password-reset-workflow.json` | `POST /api/forgot-password` | Emails a user their password reset link |
| `n8n-verification-workflow.json` | `POST /api/register`, `POST /api/resend-verification-code` | Emails a new signup their 6-digit confirmation code |

## Setup (repeat per workflow)

1. In N8N, **Workflows → Import from File** and select the workflow file.
2. Open the **Send Email** node and set your own SMTP credentials (Gmail,
   SendGrid SMTP, Mailtrap for testing, etc.) — the imported node has a
   placeholder credential that won't resolve until you point it at yours.
   Reuse the same credential across all three workflows.
3. Activate the workflow, then open the **Webhook** node and copy its
   **Production URL**.
4. Put that URL in `backend/.env`:
   - `n8n-workflow.json` → `N8N_WEBHOOK_URL`
   - `n8n-password-reset-workflow.json` → `N8N_PASSWORD_RESET_WEBHOOK_URL`
   - `n8n-verification-workflow.json` → `N8N_VERIFICATION_WEBHOOK_URL`

All three are optional — the backend degrades gracefully with any (or all)
unset (see `NFR-1` in the SRS): `/api/notify` returns a clear 503;
`/api/forgot-password` and `/api/resend-verification-code` both still
return their normal (identical either way) response, they just won't have
anything to send. `/api/register` still creates the account and asks for a
code even without this configured — there's just no email to check it in,
so verification can't be completed until it's set up.

Also set `FRONTEND_URL` in `backend/.env` (default `http://localhost:5173`)
— it's used to build the link inside the password reset email.

## Payload contracts

`n8n-workflow.json` — the backend POSTs:

```json
{
  "email": "user@example.com",
  "results": [ /* same shape as /api/match's "results" array */ ]
}
```

`n8n-password-reset-workflow.json` — the backend POSTs:

```json
{
  "email": "user@example.com",
  "resetUrl": "http://localhost:5173/?resetToken=<one-time token>"
}
```

`n8n-verification-workflow.json` — the backend POSTs:

```json
{
  "email": "user@example.com",
  "code": "482913"
}
```

By design this payload carries only the raw code, not a clickable
confirmation link — the user types it into the app themselves, the same
way a 2FA code works.

Each workflow's **Format Email** node reads its own payload and builds the
HTML email — adjust the template in that node if you want a different
layout.

## Webhook response mode

Each Webhook node is set to `responseMode: lastNode`, meaning n8n only
responds to the backend's request after the **Send Email** node has
actually run — not the moment the webhook is received. This is deliberate:
it lets the backend's HTTP status (`200` vs `502`) reflect whether the
email genuinely got sent, not just whether n8n was reachable. If the SMTP
step fails (bad credentials, provider down, etc.), the workflow errors out
and the backend surfaces that as a real failure instead of reporting
success. The tradeoff is a slightly slower response (it waits for the SMTP
round-trip), which is well within the backend's 8s cURL timeout since
sending one email is fast.
