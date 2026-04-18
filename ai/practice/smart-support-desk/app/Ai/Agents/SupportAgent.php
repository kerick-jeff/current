<?php

namespace App\Ai\Agents;

use App\Ai\Middleware\AuditPromptMiddleware;
use App\Ai\Tools\LookupPreviousTickets;
use App\Models\Document;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxSteps;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Laravel\Ai\Tools\SimilaritySearch;
use Stringable;

/**
 * SupportAgent
 *
 * This agent classifies and responds to customer support tickets.
 * It has access to:
 *   - The user's previous ticket history (via LookupPreviousTickets tool)
 *   - The knowledge base of FAQ documents (via SimilaritySearch tool)
 *
 * It returns structured output so downstream code can route tickets automatically without parsing free-form text.
 *
 * Configuration:
 *   - Provider: OpenAI (primary), Anthropic (failover handled at call site)
 *   - Max tool-call steps: 5 (prevents runaway loops)
 *   - Temperature: 0.3 (keeps responses consistent, not creative)
 *   - Max tokens: 1024 (generous for a support reply, but bounded)
 */
#[Provider(Lab::OpenAI)]
#[MaxSteps(5)]
#[MaxTokens(1024)]
#[Temperature(0.3)]
class SupportAgent implements Agent, Conversational, HasTools, HasStructuredOutput, HasMiddleware
{
    use Promptable, RemembersConversations;

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        return 'You are a helpful assistant.';
    }

    /**
     * Get the list of messages comprising the conversation so far.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return [];
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            // The category drives ticket routing in the system
            'category' => $schema->string()
                ->enum(['billing', 'account', 'technical', 'request', 'other'])
                ->required(),

            // 1 = low priority, 5 = critical (needs immediate human escalation)
            'urgency' => $schema->integer()->min(1)->max(5)->required(),

            // The actual reply the agent would send to the user
            'suggested_reply' => $schema->string()->required(),

            // True if this ticket can be closed without human intervention
            'auto_resolvable' => $schema->boolean()->required(),
        ];
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            // Lets the agent query the user's ticket history from the DB.
            new LookupPreviousTickets,

            // Lets the agent search the embedded FAQ documents for relevant answers. The agent decides when a similarity search is worth doing.
            SimilaritySearch::usingModel(
                Document::class,
                'embedding'
            )->withDescription(
                'Search the knowledge base for FAQ articles relevant to the customer\'s issue. Use this before crafting your reply to check if there is documented guidance.'
            ),
        ];
    }

    /**
     * Get the agent's middleware.
     */
    public function middleware(): array
    {
        return [
            new AuditPromptMiddleware,
        ];
    }
}
