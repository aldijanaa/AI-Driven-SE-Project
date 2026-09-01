# TravelMatch N8N automation

`n8n-workflow.json` is an importable N8N workflow: **Webhook → Format Email
(Code node) → Send Email**. The backend calls the webhook after a user asks
to have their results emailed (`POST /api/notify`).

## Setup

1. In N8N, **Workflows → Import from File** and select `n8n-workflow.json`.
2. Open the **Send Email** node and set your own SMTP credentials (Gmail,
   SendGrid SMTP, Mailtrap for testing, etc.) — the imported node has a
   placeholder credential that won't resolve until you point it at yours.
3. Activate the workflow, then open the **Webhook** node and copy its
   **Production URL**.
4. Put that URL in `backend/.env` as `N8N_WEBHOOK_URL`.

## Payload contract

The backend POSTs:

```json
{
  "email": "user@example.com",
  "results": [ /* same shape as /api/match's "results" array */ ]
}
```

The **Format Email** node reads `$json.body.email` / `$json.body.results`
and builds the HTML email from them — adjust the template in that node if
you want a different layout.
