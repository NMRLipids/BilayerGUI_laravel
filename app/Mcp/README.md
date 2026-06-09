# FAIRMD Lipids MCP Server

FAIRMD Lipids exposes a [Model Context Protocol (MCP)](https://modelcontextprotocol.io)
server so that AI assistants and other MCP clients can query the databank
programmatically. The server provides **read-only** access to lipid membrane MD
simulations and the related NMR/X-ray experiments — it never modifies data.

It is built on [`laravel/mcp`](https://github.com/laravel/mcp).

## Endpoint

The server is served over streamable HTTP and registered in
[`routes/ai.php`](../../routes/ai.php):

| Method            | URL                  |
| ----------------- | -------------------- |
| `GET`/`POST`/`DELETE` | `/mcp/fairmd-lipids` |

`POST` carries the JSON-RPC requests; `GET`/`DELETE` are used by the transport for
session streaming and teardown. The endpoint is public and rate-limited to **60
requests per minute per IP** (the `mcp` limiter defined in
[`AppServiceProvider`](../Providers/AppServiceProvider.php)).

> Note: `routes/ai.php` is loaded automatically by the `laravel/mcp` service
> provider, so it does **not** need to be registered in `bootstrap/app.php`.

## Domain model

- **Trajectories** (a.k.a. simulations): an MD run of a lipid bilayer, with a
  force field, temperature, and lipid/ion composition.
- **Quality metrics** live on each trajectory's analysis:
  - `op_quality_total` / `op_quality_headgroups` / `op_quality_tails` — agreement
    with NMR order parameters (higher is better).
  - `ff_quality` — agreement with X-ray form factors (higher is better).
  - plus `area_per_lipid` and `bilayer_thickness`.
- **Lipids**, **ions**, **membranes** and **experiments** (type `FF` = form
  factor, `OP` = order parameter).

## Tools

All tools are read-only and idempotent.

| Tool                        | Purpose |
| --------------------------- | ------- |
| `search-database`           | Free-text search across lipids, ions, experiments and trajectories. Accepts plain text (partial match with `*`/`?` wildcards), quoted text for exact match, a DOI, or `ID<number>` to look up a trajectory directly. |
| `advanced-trajectory-search`| Filter trajectories by lipid/ion/membrane composition, force field and numeric ranges (temperature, quality metrics), with sorting and pagination. Composition filters accept per-item logical operators (`and`/`or`/`not`). |
| `best-simulations`          | Rank simulations by the rank-product of OP and FF quality (lower product = better). Optionally restrict to simulations containing a specific lipid. |
| `get-trajectory`            | Fetch full detail for a single trajectory by numeric ID. Set `include_plot_data` to also return the (large) area-per-lipid and form-factor plot arrays. |
| `get-experiment`            | Fetch full detail for a single experiment by `type` (`FF`/`OP`) and `path`. |
| `get-lipid`                 | Fetch full detail for a single lipid by numeric ID or molecule name, including properties, synonyms and cross-references. |
| `database-statistics`       | High-level totals (trajectories, membranes, experiments, last update). Optionally include a per-force-field breakdown. |

The tool classes live in [`app/Mcp/Tools/`](Tools), the server definition in
[`app/Mcp/Servers/FairmdLipidsServer.php`](Servers/FairmdLipidsServer.php), and the
underlying query logic in
[`app/Services/`](../Services) (`SearchQueryService`, `SimulationQueryService`,
`StatisticsService`).

## Trying it out

### MCP Inspector

```bash
php artisan mcp:inspector fairmd-lipids
```

#### Via Laravel Sail

The inspector's web UI (port `6274`) and proxy (port `6277`) must be reachable
from your host browser, so expose them on the `laravel.test` service in
`docker-compose.yml` and recreate the container:

```yaml
        ports:
            - '${APP_PORT:-80}:80'
            - '${VITE_PORT:-5173}:${VITE_PORT:-5173}'
            - '6274:6274'   # MCP Inspector UI
            - '6277:6277'   # MCP Inspector proxy
```

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan mcp:inspector fairmd-lipids --host=0.0.0.0
```

Then open the tokenized URL printed in the terminal
(`http://localhost:6274/?MCP_PROXY_AUTH_TOKEN=…`). The `--host=0.0.0.0` flag binds
the inspector to all interfaces so it is reachable from outside the container.

### Raw HTTP (curl)

```bash
# 1. Initialize a session (capture the MCP-Session-Id response header)
curl -sS -D - -o /dev/null -X POST http://127.0.0.1:8000/mcp/fairmd-lipids \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json, text/event-stream' \
  -d '{"jsonrpc":"2.0","id":1,"method":"initialize","params":{"protocolVersion":"2025-06-18","capabilities":{},"clientInfo":{"name":"curl","version":"1"}}}'

# 2. Send the initialized notification, then call a tool, reusing MCP-Session-Id
curl -sS -X POST http://127.0.0.1:8000/mcp/fairmd-lipids \
  -H 'Content-Type: application/json' \
  -H 'Accept: application/json, text/event-stream' \
  -H 'MCP-Session-Id: <id-from-step-1>' \
  -d '{"jsonrpc":"2.0","id":2,"method":"tools/call","params":{"name":"search-database","arguments":{"text":"POPC"}}}'
```

## Connecting MCP clients

The server speaks streamable HTTP. Replace the URL below with our server's actual
public URL (e.g. `https://lipids.fairmd.org/mcp/fairmd-lipids`) or the public URL of your deployment; the examples use the local
dev server.

### HTTP-native clients (VS Code, Cursor, etc.)

Clients that support remote MCP servers can connect to the URL directly:

```json
{
  "mcpServers": {
    "fairmd-lipids": {
      "type": "http",
      "url": "http://127.0.0.1:8000/mcp/fairmd-lipids"
    }
  }
}
```

### Claude Desktop

Claude Desktop launches MCP servers as local commands, so point it at the HTTP
endpoint through the [`mcp-remote`](https://www.npmjs.com/package/mcp-remote)
bridge (requires Node.js). Add this to your
`claude_desktop_config.json` (Settings → Developer → Edit Config):

```json
{
  "mcpServers": {
    "fairmd-lipids": {
      "command": "npx",
      "args": ["-y", "mcp-remote", "http://127.0.0.1:8000/mcp/fairmd-lipids"]
    }
  }
}
```

Restart Claude Desktop after saving; the FAIRMD Lipids tools will then appear in
the client.

## Discoverability

Once a client knows the endpoint URL, MCP is self-describing: `initialize` plus
`tools/list` expose the full, schema-annotated interface. MCP has **no automatic
crawl/registry mechanism**, so the remaining work is making the endpoint findable.

Levers, in rough order of impact:

1. **List the server in MCP registries** — the primary way clients and users
   discover MCP servers (e.g. the official MCP registry, the Anthropic/Claude
   directory, mcp.so, Glama, PulseMCP, Smithery).
2. **Advertise the endpoint on indexable pages** so web/AI crawlers associate
   "FAIRMD Lipids" with the MCP endpoint:
   - Frontpage: [`resources/views/welcome.blade.php`](../../resources/views/welcome.blade.php)
     — add a human-readable mention/link to `/mcp/fairmd-lipids`.
   - Shared `<head>` meta: [`resources/views/layouts/head.blade.php`](../../resources/views/layouts/head.blade.php)
     — the `<meta name="description">` / Open Graph block is the place to surface
     that the databank offers an MCP interface.
3. **Publish machine-readable advertisements** at the site root:
   - `/.well-known/mcp` — a well-known descriptor pointing at the endpoint.
   - `/llms.txt` — a plain-text summary for LLM-based crawlers, linking the
     endpoint and this document.

   Client support for both is still emerging, so treat them as low-cost
   nice-to-haves rather than load-bearing discovery.

> The dev-server [`public/robots.txt`](../../public/robots.txt) disallows all
> crawling as a safeguard; the deployed/production `robots.txt` allows crawling,
> so the indexable-page advertisements above take effect in production.

## Tests

Feature tests for the server and its tools live in
[`tests/Feature/McpServerTest.php`](../../tests/Feature/McpServerTest.php):

```bash
php artisan test tests/Feature/McpServerTest.php
```
