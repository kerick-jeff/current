<?php

namespace App\Ai\Tools;

use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

/**
 * This tool is available to the SupportAgent.
 * When the agent needs context about a user's history (for example, to avoid suggesting a fix that was already tried it calls this tool with the user_id and the SDK executes the handle() method on our behalf.
 * The agent decides WHEN to call it. We decide WHAT it returns.
 */
class LookupPreviousTickets implements Tool
{
    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return 'Look up the previous support tickets submitted by a given user. Returns the subject, status, and AI category of their last 5 tickets. Use this to understand if the user has reported this issue before.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $tickets = Ticket::where('user_id', $request['user_id'])
            ->latest()
            ->limit(5)
            ->get(['subject', 'status', 'ai_category', 'created_at']);

        if ($tickets->isEmpty()) {
            return 'No previous tickets found for this user.';
        }

        return $tickets->map(function (Ticket $ticket) {
            return sprintf(
                '[%s] %s — status: %s, category: %s',
                $ticket->created_at->toDateString(),
                $ticket->subject,
                $ticket->status,
                $ticket->ai_category ?? 'unclassified'
            );
        })->implode("\n");
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'user_id' => $schema->integer()->required(),
        ];
    }
}
