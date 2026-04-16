<?php

namespace App\Http\Controllers;

use App\Ai\Agents\SupportAgent;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Ai\Enums\Lab;

class SupportController extends Controller
{
    /**
     * POST /api/support/tickets/{ticket}/analyze
     *
     * Analyzes a single ticket and persists the structured AI output.
     * Uses OpenAI as primary provider with Anthropic as failover.
     */
    public function analyze(Request $request, Ticket $ticket): JsonResponse
    {
        $user = $request->user();

        // Ensure the authenticated user owns this ticket
        abort_unless($ticket->user_id === $user->id, 403);

        $agent = SupportAgent::make(user: $user);

        // The provider (failover) array means: try OpenAI first, fall back to Anthropic if a rate limit or outage is encountered.
        // The agent configuration already sets #[Provider(Lab::OpenAI)], but we override per-call for failover.
        $response = $agent->prompt(
            "Please analyze this support ticket and classify it:\n\n"
            . "Subject: {$ticket->subject}\n\n"
            . "Message: {$ticket->body}\n\n"
            . "User ID for history lookup: {$ticket->user_id}",
            provider: [Lab::OpenAI, Lab::Anthropic],
        );

        // $response behaves like an array because the agent returns structured output
        $analysis = [
            'category'        => $response['category'],
            'urgency'         => $response['urgency'],
            'suggested_reply' => $response['suggested_reply'],
            'auto_resolvable' => $response['auto_resolvable'],
        ];

        // Persist the AI analysis back onto the ticket
        $ticket->update([
            'ai_category'        => $analysis['category'],
            'ai_urgency'         => $analysis['urgency'],
            'ai_suggested_reply' => $analysis['suggested_reply'],
            'ai_auto_resolvable' => $analysis['auto_resolvable'],
            'ai_analysis'        => $analysis,
        ]);

        return response()->json([
            'ticket_id' => $ticket->id,
            'analysis'  => $analysis,
        ]);
    }

    /**
     * POST /api/support/chat
     *
     * Starts a new AI support conversation.
     * Returns the reply and a conversation_id the client should store for follow-up messages.
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $user = $request->user();

        $response = SupportAgent::make(user: $user)
            ->forUser($user)
            ->prompt(
                $request->input('message'),
                provider: [Lab::OpenAI, Lab::Anthropic],
            );

        return response()->json([
            'reply'           => $response['suggested_reply'],
            'category'        => $response['category'],
            'urgency'         => $response['urgency'],
            'auto_resolvable' => $response['auto_resolvable'],
            'conversation_id' => $response->conversationId,
        ]);
    }

    /**
     * POST /api/support/chat/{conversationId}/continue
     *
     * Continues an existing conversation.
     * The RemembersConversations trait on the agent automatically loads previous messages from the DB.
     */
    public function continueChat(Request $request, string $conversationId): JsonResponse
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $user = $request->user();

        $response = SupportAgent::make(user: $user)
            ->continue($conversationId, as: $user)
            ->prompt(
                $request->input('message'),
                provider: [Lab::OpenAI, Lab::Anthropic],
            );

        return response()->json([
            'reply'           => $response['suggested_reply'],
            'category'        => $response['category'],
            'urgency'         => $response['urgency'],
            'auto_resolvable' => $response['auto_resolvable'],
            'conversation_id' => $conversationId,
        ]);
    }
}
