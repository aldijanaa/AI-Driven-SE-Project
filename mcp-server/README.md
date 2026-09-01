# TravelMatch MCP server

Exposes TravelMatch's destination-matching engine as an MCP tool,
`get_travel_recommendations`, so it can be called from Claude Desktop,
Claude Code, or any other MCP client. It's a thin wrapper — the actual
matching/RAG logic lives in the PHP backend; this just proxies to it.

## Run

Requires the PHP backend running (`php -S localhost:8000 -t backend` from
the repo root).

```
cd mcp-server
npm install
npm start
```

## Register with Claude Code / Claude Desktop

Add to your MCP config (`.mcp.json` for Claude Code, or the Desktop app's
config file):

```json
{
  "mcpServers": {
    "travelmatch": {
      "command": "node",
      "args": ["mcp-server/index.js"],
      "env": {
        "TRAVELMATCH_BACKEND_URL": "http://localhost:8000/api"
      }
    }
  }
}
```
