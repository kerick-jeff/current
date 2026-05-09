# Smart Support Desk

A REST API demonstrating major features of the **Laravel AI SDK** (`laravel/ai`). Built as a backend for an AI-assisted customer support system.

---

## What this covers

| SDK Feature | Where it's used |
|---|---|
| Agents | `SupportAgent` — classifies and responds to tickets |
| Tools | `LookupPreviousTickets` — agent queries DB mid-response |
| Structured Output | Agent returns typed schema, not raw text |
| Conversation Memory | `RemembersConversations` trait on the agent |
| Streaming | `GET /api/support/stream` SSE endpoint |
| Queueing | `POST /api/support/tickets/analyze/bulk` dispatches batch jobs |
| Embeddings + RAG | FAQ docs embedded into pgvector, used via `SimilaritySearch` tool |
| Agent Middleware | `AuditPromptMiddleware` logs every prompt + token usage |
| Agent Config | PHP attributes for provider, model, temperature |
| Failover | OpenAI primary, Anthropic backup on every prompt call |
| Testing | Full Feature test suite using `::fake()` utilities |

---

## Architecture

```
app/
├── Ai/
│   ├── Agents/
│   │   └── SupportAgent.php          # Main agent class
│   ├── Middleware/
│   │   └── AuditPromptMiddleware.php  # Logs every prompt
│   └── Tools/
│       └── LookupPreviousTickets.php  # DB tool for agent
│
├── Http/
│   └── Controllers/
│       ├── SupportController.php     # Analyze, chat, stream
│       └── KnowledgeBaseController.php
│
├── Models/
│   ├── Ticket.php
│   ├── AuditLog.php
│   └── Document.php                  # Vector-enabled model
│
└── Console/
    └── Commands/
        └── SeedKnowledgeBase.php     # Embeds FAQ .md files

database/
└── migrations/
    ├── create_tickets_table.php
    ├── create_audit_logs_table.php
    └── create_documents_table.php    # Includes vector column

knowledge-base/                       # FAQ markdown files
├── password-reset.md
├── billing.md
└── account-limits.md
```

---

## Prerequisites

- PHP 8.2+
- Laravel 13
- PostgreSQL with `pgvector` extension
- An OpenAI API key (primary)
- An Anthropic API key (failover)

---

## Setup

```bash
git clone https://github.com/kerick-jeff/smart-support-desk
cd smart-support-desk
composer install

cp .env.example .env
php artisan key:generate
```

Update `.env`:

```env
DB_CONNECTION=pgsql
DB_DATABASE=smart_support_desk

OPENAI_API_KEY=sk-...
ANTHROPIC_API_KEY=sk-ant-...
```

Run migrations:

```bash
php artisan migrate
```

Seed the knowledge base (embeds the FAQ files into pgvector):

```bash
php artisan kb:seed
```

Start queue worker (needed for bulk analysis):

```bash
php artisan queue:work
```

---

## API Endpoints

### Analyze a single ticket

```
POST /api/support/tickets/{id}/analyze
Authorization: Bearer {token}
```

Returns structured output: `category`, `urgency`, `suggested_reply`, `auto_resolvable`.

### Start a support chat

```
POST /api/support/chat
Authorization: Bearer {token}
Body: { "message": "I can't reset my password" }
```

Returns: `{ "reply": "...", "conversation_id": "uuid" }`

### Continue a chat

```
POST /api/support/chat/{conversationId}/continue
Authorization: Bearer {token}
Body: { "message": "I tried that already" }
```

### Stream a response (SSE)

```
GET /api/support/stream?message=hello
Authorization: Bearer {token}
```

Returns a streaming Server-Sent Events response.

### Bulk analyze tickets

```
POST /api/support/tickets/analyze/bulk
Authorization: Bearer {token}
Body: { "ticket_ids": [1, 2, 3, 4, 5] }
```

Dispatches each to the queue. Results saved to `tickets.ai_analysis`.

---

## Running tests

```bash
php artisan test
```

All AI calls are faked in tests. No real API keys needed to run the test suite.

---

## Key design decisions

**Why structured output instead of raw text?**
In a real support system, downstream code needs to know the urgency level to route tickets. Raw text answers are not machine-readable. `HasStructuredOutput` with a strict schema makes the agent's output predictable.

**Why pgvector instead of a vector store provider?**
Keeps infrastructure simple. No third-party vector DB dependency. Makes it easy for deployment in a constrained environment (DigitalOcean Droplet or single-server AWS).

**Why audit middleware instead of event listeners?**
The `HasMiddleware` contract gives access to the full `AgentPrompt` object before and after, which includes model name, token usage, and the raw prompt. Events give you less context.

---

## License
MIT
