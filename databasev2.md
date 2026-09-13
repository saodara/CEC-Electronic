# Neon Database — Deep Reference

A complete reference on **Neon** (neon.tech) — what it is, how every piece of the platform works, and how to use it to build a "book" project (an online bookstore / library catalog) on top of this Laravel app. Sourced from the official Neon docs (neon.com/docs) as of 2026-09-12.

---

## 1. What Neon Actually Is

Neon is **not just "Postgres in the cloud."** It's a backend platform built around a serverless Postgres core, with several bundled services that all branch together with your project:

| Service | Status | What it gives you |
|---|---|---|
| **Postgres** | GA | Serverless, autoscaling, branchable Postgres 14–17 |
| **Auth** | GA | Managed authentication (users/sessions live in your own Postgres) |
| **Object Storage** | Public beta | S3-compatible file storage that branches with your DB |
| **Functions** | Public beta | Long-running serverless compute next to your data (APIs, WebSockets, SSE, agents) |
| **AI Gateway** | Public beta | One API key for OpenAI/Anthropic/Google/etc. models, with routing/logging/cost controls |

Beta services (Object Storage, Functions, AI Gateway) are currently only available on **new projects created in `us-east-2`** — they can't be retrofitted onto an existing project.

**Mental model:** Neon is the backend your app talks to — it doesn't host your app itself. You'd deploy the Laravel app the way you already do (this repo has `DEPLOYMENT.md` for that), and point it at a Neon Postgres connection string the same way you'd point it at RDS or a local Postgres instance — except Neon adds branching, scale-to-zero, and the extra services above.

---

## 2. Architecture: Why Neon Behaves Differently From Normal Postgres

Traditional (self-hosted / RDS) Postgres ties your data to a single VM with a local disk. Neon **separates compute from storage**:

- **Compute** — stateless Postgres processes (the thing that runs your queries). Because it's stateless, it can be created, resized, suspended, or destroyed instantly with zero data loss.
- **Storage** — a durable, multi-tiered layer:
  - **Safekeepers** replicate incoming WAL (write-ahead log) via a Paxos-like consensus protocol, so a write is durable the instant it's acknowledged.
  - **Pageserver** reconstructs whatever page version a query needs on demand, from the WAL history.
  - **Object storage** (S3-like) holds the immutable long-term history, off the hot path of normal queries.

Because of this split:

- **Branching is instant and cheap** — a branch doesn't copy data; it's a pointer into existing WAL history, with copy-on-write for anything new/changed. A multi-hundred-GB database can be "branched" in about a second.
- **Scale-to-zero is just detaching a stateless compute process** — nothing to flush to disk, so suspend/resume is fast (typically a few hundred ms cold start) rather than a real "server boot."
- **Point-in-time restore** falls out for free — you can spin up a branch as of any timestamp within your plan's history-retention window.

### Key vocabulary

| Term | Meaning |
|---|---|
| **Project** | The top-level container for one application's Neon resources (branches, roles, databases, etc.) |
| **Branch** | A copy-on-write clone of your data at some point in time (like a git branch, but for the whole database) |
| **Compute endpoint** | The actual running Postgres process attached to a branch — this is what has CPU/RAM and an autoscaling range |
| **Role** | A Postgres user/login (Neon manages these alongside normal Postgres role permissions) |
| **Database** | A regular Postgres database (schemas/tables/etc.) living inside a branch |

---

## 3. Branching — the headline feature

**How it works:** `CREATE BRANCH` doesn't copy bytes. The new branch shares all history with its parent up to the branch point, and only diverges (copy-on-write) as new writes happen on either side. This means:

- Creating a branch of a multi-GB production database is near-instant and doesn't slow down the parent.
- Each branch gets **its own compute endpoint**, so a dev branch's heavy query load can't starve production.

**History retention window** (how far back you can branch-from / restore-to):

| Plan | Retention |
|---|---|
| Free | 6 hours |
| Launch | up to 7 days |
| Scale | up to 30 days |

**Common branch workflows:**

1. **Per-feature dev branches** — branch off `main` for every feature/PR, seeded with real (or scrubbed) production data, then delete it when the PR merges. No more "works on my machine" schema drift.
2. **Preview environments** — CI/CD (GitHub Actions, Vercel, etc.) creates a branch per PR automatically; tear down on merge/close.
3. **Testing / destructive migrations** — branch, run the risky migration or seed script, verify, then either promote or discard. Never touch prod directly.
4. **Point-in-time recovery** — "branch from a timestamp" to recover from an accidental `DELETE`/`DROP`, inspect the old data, then cherry-pick or restore it.
5. **Schema-only branches** — clone structure without sensitive row data, useful for handing a safe sandbox to contractors/CI.

**Managing branches (CLI):**

```bash
neon branches create --name feature/add-book-reviews
neon branches list
neon branches reset feature/add-book-reviews --parent   # reset to match parent
neon branches delete feature/add-book-reviews
```

Branches (and their computes) can also carry a **TTL** so throwaway dev/preview branches clean themselves up automatically — very useful if you spin up one branch per book-catalog import job or per test run.

---

## 4. Autoscaling & Scale-to-Zero

**Autoscaling:** compute size is measured in **CU (Compute Units)** — 1 CU ≈ 1 vCPU + 4 GB RAM, and it scales linearly (2 CU = 8 GB RAM, 8 CU = 32 GB RAM, etc.). You set a **min** and **max** CU for a branch's compute; Neon adjusts within that range in response to load automatically, with no restart. The maximum allowed *range* between min and max is 8 CU on most plans (larger fixed sizes are available separately, up to 56 CU on Scale).

**Scale-to-zero:** if a compute sees no activity for the **suspend timeout** (default 5 minutes), Neon suspends it entirely — literally $0 compute cost while idle. The next incoming query wakes it back up in roughly a few hundred milliseconds.

- **Free plan:** scale-to-zero is mandatory, can't be disabled.
- **Paid plans:** you can disable it (keep compute always-on) if cold-start latency is unacceptable for a given branch (e.g., a public-facing production API).
- **Computes larger than 16 CU** stay always-on regardless of settings.
- A compute with an active **logical replication subscriber** won't suspend.

**Why this matters for a "book" project:** if you're prototyping (a personal library catalog, a bookstore side project, a low-traffic app), scale-to-zero means you pay close to nothing while it's not being used — ideal for a dev branch or a low-traffic production app. For a real storefront with steady traffic, disable scale-to-zero on the production branch once you're past prototyping, so customers never eat a cold-start delay.

---

## 5. Connecting — Pooled vs Direct

Neon fronts Postgres with **PgBouncer** for connection pooling, because serverless/edge environments (Lambda, Vercel functions, etc.) open far more short-lived connections than a normal Postgres `max_connections` can handle.

- **Pooled connection string** — hostname has a `-pooler` suffix:
  `postgresql://user:pass@ep-xxxx-pooler.region.aws.neon.tech/dbname?sslmode=require`
  Use this for normal application traffic (web requests, Laravel's default DB connection).

- **Direct (unpooled) connection string** — no `-pooler` suffix. Use this for:
  - Schema migrations (`php artisan migrate`), `pg_dump`/`pg_restore`
  - Logical replication
  - Anything needing session-level state across statements (advisory locks held across queries, `LISTEN/NOTIFY`, session-level `SET`, temp tables that must survive multiple statements)

**Important limitation:** the pooler runs in **transaction mode** — a connection is returned to the pool after each transaction, so anything relying on session state that must persist *between* transactions (prepared statements at the protocol level, session `SET`, etc.) will misbehave over the pooled connection. Workaround: `ALTER ROLE your_role SET some_setting = ...` to persist a setting at the role level instead of the session level.

**Limits:** PgBouncer allows up to 10,000 client connections; the pool size per user/database defaults to ~90% of the compute's `max_connections` (which itself ranges roughly 104–4,000 depending on compute size).

### Laravel `.env` setup

```env
DB_CONNECTION=pgsql
DB_HOST=ep-xxxx-pooler.region.aws.neon.tech
DB_PORT=5432
DB_DATABASE=neondb
DB_USERNAME=neondb_owner
DB_PASSWORD=your-password
DB_SSLMODE=require
```

Laravel's `pgsql` connection needs `sslmode=require` — either as a query param on the URL or via `'sslmode' => env('DB_SSLMODE', 'require')` in `config/database.php`'s `pgsql` array. For migrations specifically, point a **separate** connection (e.g. `pgsql_direct`) at the non-pooled host and run `php artisan migrate --database=pgsql_direct` in CI/deploy, while the app itself uses the pooled connection.

---

## 6. Read Replicas

Read replicas are additional **read-only compute endpoints** attached to the *same storage* as your primary branch — no data duplication, so no extra storage cost. They:

- Spin up in seconds.
- Get their own autoscaling and scale-to-zero settings, independent of the primary.
- Are **asynchronous** (eventually consistent) — safekeepers stream changes to them.
- Must be in the **same region** as the primary (cross-region requires logical replication into a separate project instead).

Use cases: offload analytics/reporting queries (e.g., "top-selling books this month") away from your write path, horizontally scale read traffic, or give a BI tool / read-only dashboard user access without write risk. Free plan allows up to 3 read-replica computes per project.

---

## 7. Postgres Extensions (relevant to a book catalog)

Neon supports the standard Postgres extension ecosystem. Ones especially useful for a book/library/bookstore app:

- **Full-text search** (built into core Postgres, no extension needed) — `tsvector`/`tsquery` columns + a GIN index for searching titles/authors/descriptions.
- **`pgvector`** — turns Postgres into a vector store. Useful for "find similar books" / semantic search over descriptions or embeddings-based recommendations, combined with the AI Gateway (§11) to generate embeddings.
- **`pg_trgm`** — trigram similarity, great for fuzzy/typo-tolerant search on titles and author names (`ILIKE '%harry potter%'`-style queries made fast with a GIN/GiST trigram index).
- **`pgcrypto`** — hashing/encryption functions if you need anything beyond Laravel's own bcrypt (e.g., encrypting a customer's stored data at rest).
- **`pg_stat_statements`** — query performance stats; turn this on early so you can see which catalog/search queries are slow before it's a production fire.

Enable any of these with plain SQL, no special Neon step needed:

```sql
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE EXTENSION IF NOT EXISTS vector;
CREATE EXTENSION IF NOT EXISTS pgcrypto;
CREATE EXTENSION IF NOT EXISTS pg_stat_statements;
```

---

## 8. Neon Auth

Neon Auth ("Managed Better Auth") is a managed authentication layer where **your Neon Postgres database is the source of truth** for users/sessions — no separate auth database, no webhooks to keep two systems in sync. You get:

- User/session tables that live in your own database (queryable with plain SQL, joinable with your `books`/`orders`/etc. tables).
- SDKs (`@neondatabase/auth`, `@neondatabase/auth-ui`) for JS frameworks (Next.js, React SPA/React Router).

**For this repo specifically:** you already have Laravel's own auth (`app/Http/Controllers/Customer/AuthController.php`, `database/factories/UserFactory.php`) — Neon Auth is a JS/TS-ecosystem product, so it's most relevant if you build a *separate* headless storefront or admin frontend (Next.js/React) against the same Neon Postgres database rather than replacing Laravel's built-in auth. For a pure-Laravel app, keep using Laravel's native auth/Sanctum/Breeze and just use Neon for the Postgres database itself.

---

## 9. Neon Data API

A fully managed **auto-generated REST API** over your Postgres schema (PostgREST-style): point it at a schema and it exposes tables/views as REST resources, with Postgres **Row-Level Security (RLS)** enforcing per-user access. Auth for the Data API is verified either via Neon Auth or an external JWT-issuing IdP.

Useful if you want a lightweight client (mobile app, static frontend) to query `books`, `authors`, `reviews` etc. directly over HTTP without writing a dedicated backend endpoint for every read. For this Laravel project, you likely don't need it — Laravel's own routes/controllers already serve that role — but it's worth knowing about if you spin off a decoupled frontend later.

---

## 10. Neon Object Storage

S3-compatible object storage that **branches with your database** — so a dev branch gets its own isolated copy-on-write view of uploaded files (e.g., book cover images), matching the branch's data state. Access is via standard S3 clients/SDKs using Neon-issued credentials mapped to S3-style access keys. Public beta, `us-east-2`-only new projects.

For a book project, this is the natural place to store **cover images / EPUB or PDF file uploads** if you want them to branch alongside the catalog data itself (e.g., testing an import script against a dev branch without touching production files). This repo currently stores images under `public/images/...` — Neon Object Storage would be an alternative to S3/Spaces should you want storage that branches with the DB.

---

## 11. Neon Functions

Long-running serverless functions that execute **next to your database** (same network, low latency) — meant for things that don't fit typical short-lived serverless (Lambda-style) functions: WebSocket servers, Server-Sent Events streams, long AI-agent HTTP calls, custom REST APIs, or even hosting an MCP server. Deployed/managed via the Neon CLI (`neon functions deploy`, etc.) or `neon.ts` infra-as-code. Public beta, `us-east-2`-only.

Not typically needed for a standard Laravel storefront (Laravel's own hosting already handles the API layer), but relevant if you later add something like live inventory/order notifications over WebSockets and want it colocated with the DB rather than bolted onto the PHP app.

---

## 12. Neon AI Gateway

A single API endpoint/credential that proxies to many LLM providers (OpenAI, Anthropic, Google, open-source models via Databricks), with routing, logging, and cost controls, and an OpenAI-compatible `/v1/models` endpoint to discover what's servable. For a book app, this is what you'd use to:

- Generate **embeddings** for book descriptions (paired with `pgvector`, §7) for semantic "similar books" recommendations.
- Power an **AI-assisted search or chat-with-your-catalog** feature without managing separate provider API keys/billing per provider.

---

## 13. Infrastructure-as-Code: `neon.ts`

Instead of manually toggling settings in the console, you can declare a project's services and per-branch policy in a single TypeScript file (`neon.ts`), then reconcile it with the CLI:

```typescript
import { defineConfig } from "@neon/config/v1";

export default defineConfig({
  auth: true,
  dataApi: true,
  branch: (branch) => {
    if (branch.exists) return {}; // don't touch existing branches
    if (branch.name.startsWith("dev")) {
      return {
        ttl: "7d",
        postgres: {
          computeSettings: {
            autoscalingLimitMinCu: 0.25,
            autoscalingLimitMaxCu: 1,
            suspendTimeout: "5m",
          },
        },
      };
    }
    return {};
  },
});
```

```bash
neon config status   # read-only: current live config
neon config plan     # read-only: dry-run diff
neon config apply    # provision what's declared (alias: neon deploy)
```

This is JS/TS-ecosystem tooling; a pure-PHP/Laravel project can ignore it and just manage branches via the CLI/console/API directly, but it's worth knowing if you ever add a Node-based tool (a Next.js admin panel, a script runner) alongside this Laravel app.

---

## 14. CLI & Branch-First Dev Loop

```bash
npm i -g neon              # install CLI
neon link                  # once per project: link workspace to org/project/branch, writes .neon
neon checkout dev-books     # create/switch to a branch; auto-pulls its DATABASE_URL into .env
```

`neon link`/`neon checkout` write the branch's connection info straight into your `.env` (or `.env.local`), so the practical Laravel loop looks like:

```bash
neon checkout feature/book-reviews   # fresh, isolated Postgres branch + updated .env
php artisan migrate --database=pgsql_direct
php artisan db:seed
# build/test the feature against this isolated branch
neon checkout main                   # back to your main dev branch when done
```

This gives every feature branch its own real Postgres database — no more shared dev DB drifting out of sync with migrations, and no risk of one feature's test data polluting another's.

---

## 15. Pricing Snapshot

| | Free | Launch | Scale |
|---|---|---|---|
| Cost | $0 | Pay-as-you-go | Pay-as-you-go |
| Storage | 0.5 GB | Unlimited, $0.35/GB-mo | Unlimited, $0.35/GB-mo |
| Compute | 100 CU-hrs/mo | $0.106/CU-hr | $0.222/CU-hr |
| Branches | 10/project | 10/project (+$1.50/mo each extra) | 25/project (+$1.50/mo each extra) |
| History retention | 6 hours | up to 7 days | up to 30 days |
| Autoscaling ceiling | 2 CU | 16 CU | 16 CU (fixed sizes to 56 CU) |
| Network egress | 5 GB/mo | 500 GB/mo, then $0.10/GB | 500 GB/mo, then $0.10/GB |
| Extras | — | Billing support | SOC 2/ISO, SLAs, private networking |

For a "book" side project: **Free** is plenty to prototype the whole catalog + orders schema and iterate with branches. Move to **Launch** once you have real traffic/storage beyond 0.5 GB or need more than 6 hours of point-in-time recovery.

---

## 16. Applying This to "Book" — Suggested Schema & Setup

If the goal is a book catalog/store (similar shape to the existing `Category`/`Supplier`/`Order`/`CartItem` models in this repo), a Neon-backed Postgres schema might look like:

```sql
CREATE TABLE authors (
    id BIGSERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    bio TEXT,
    created_at TIMESTAMPTZ DEFAULT now(),
    updated_at TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE books (
    id BIGSERIAL PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    isbn VARCHAR(20) UNIQUE,
    author_id BIGINT REFERENCES authors(id) ON DELETE SET NULL,
    category_id BIGINT REFERENCES categories(id) ON DELETE SET NULL,
    description TEXT,
    price NUMERIC(10,2) NOT NULL,
    stock INTEGER NOT NULL DEFAULT 0,
    cover_image VARCHAR(255),
    search_vector tsvector,
    created_at TIMESTAMPTZ DEFAULT now(),
    updated_at TIMESTAMPTZ DEFAULT now()
);

-- Full-text search over title + description
CREATE INDEX books_search_idx ON books USING GIN (search_vector);

CREATE FUNCTION books_search_trigger() RETURNS trigger AS $$
BEGIN
  NEW.search_vector :=
    setweight(to_tsvector('english', coalesce(NEW.title,'')), 'A') ||
    setweight(to_tsvector('english', coalesce(NEW.description,'')), 'B');
  RETURN NEW;
END
$$ LANGUAGE plpgsql;

CREATE TRIGGER books_search_update
  BEFORE INSERT OR UPDATE ON books
  FOR EACH ROW EXECUTE FUNCTION books_search_trigger();

-- Fuzzy title/author search
CREATE EXTENSION IF NOT EXISTS pg_trgm;
CREATE INDEX books_title_trgm_idx ON books USING GIN (title gin_trgm_ops);
```

Laravel-side equivalents would be a `books` migration + `Book`/`Author` Eloquent models mirroring the existing `Category`/`Supplier` model style already in `app/Models/`, with a `BookFactory` following the pattern of the other factories already in `database/factories/`.

**Recommended workflow using what's covered above:**

1. `neon link` this project to a new Neon project (Free plan is fine to start).
2. `neon checkout dev-books` to get an isolated branch + `.env` populated automatically.
3. Add the schema above via a Laravel migration, run it against the **direct** (non-pooled) connection.
4. Point the app's normal `DB_CONNECTION` at the **pooled** host for request traffic.
5. Use `pg_trgm`/`tsvector` for search now; add `pgvector` + the AI Gateway later if you want semantic "similar books" recommendations.
6. Before going live, review the production branch's autoscaling range and consider disabling scale-to-zero on it once traffic is no longer sporadic.

---

## 17. Sources

All details above were pulled from the official Neon docs on 2026-09-12:
- neon.com/docs/introduction/architecture-overview
- neon.com/docs/introduction/branching
- neon.com/docs/introduction/autoscaling
- neon.com/docs/introduction/scale-to-zero
- neon.com/docs/introduction/read-replicas
- neon.com/docs/connect/connection-pooling
- neon.com/docs/guides/neon-auth
- neon.com/docs/data-api/overview
- neon.com/docs/storage/overview
- neon.com/docs/compute/functions/overview
- neon.com/docs/ai-gateway/overview
- neon.com/docs/introduction/plans
- neon.com/docs/extensions/pg-extensions

Neon ships fast — re-fetch any `.md`-suffixed doc URL above for the latest details before relying on specifics (limits, pricing, CU ranges) in a real deployment decision.
