<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;
use Stringable;

class SupportAgent implements Agent, Conversational, HasTools, HasStructuredOutput
{
    use Promptable;

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
        return [];
    }
}
