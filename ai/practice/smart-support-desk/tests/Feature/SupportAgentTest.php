<?php

namespace Tests\Feature;

use App\Ai\Agents\SupportAgent;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Embeddings;
use Tests\TestCase;

/**
 * SupportAgentTest
 *
 * These tests cover the SupportController endpoints.
 * All AI calls are faked — no real API keys needed, no HTTP calls made.
 *
 * Key pattern: We call SupportAgent::fake() before any test that triggers the agent.
 * The SDK will intercept the prompt call and return our fake data instead of hitting the provider.
 */
class SupportAgentTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Ticket Analysis
    // -------------------------------------------------------------------------

    /** @test */
    public function it_analyzes_a_ticket_and_persists_structured_output(): void
    {
        // Arrange
        $user   = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $user->id]);

        // Let the SDK return this structured data for every prompt.
        // The keys match what SupportAgent::schema() defines.
        SupportAgent::fake([
            [
                'category'        => 'billing',
                'urgency'         => 3,
                'suggested_reply' => 'Your invoice can be found in the billing portal.',
                'auto_resolvable' => true,
            ],
        ]);

        // Act
        $response = $this->actingAs($user)->postJson("/api/support/tickets/{$ticket->id}/analyze");

        // Assert HTTP response
        $response->assertOk()
            ->assertJsonStructure([
                'ticket_id',
                'analysis' => [
                    'category',
                    'urgency',
                    'suggested_reply',
                    'auto_resolvable',
                ],
            ]);

        $response->assertJsonPath('analysis.category', 'billing');
        $response->assertJsonPath('analysis.urgency', 3);

        // Assert the ticket was updated in the database
        $this->assertDatabaseHas('tickets', [
            'id'             => $ticket->id,
            'ai_category'    => 'billing',
            'ai_urgency'     => 3,
            'ai_auto_resolvable' => true,
        ]);

        // Assert the agent was actually prompted (not skipped silently)
        SupportAgent::assertPrompted(function ($prompt) {
            return $prompt->contains('Analyze this support ticket');
        });
    }

    /** @test */
    public function it_rejects_analysis_of_another_users_ticket(): void
    {
        SupportAgent::fake();

        $owner  = User::factory()->create();
        $other  = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($other)
            ->postJson("/api/support/tickets/{$ticket->id}/analyze")
            ->assertForbidden();

        // The agent should never have been called
        SupportAgent::assertNeverPrompted();
    }

    // -------------------------------------------------------------------------
    // Chat
    // -------------------------------------------------------------------------

    /** @test */
    public function it_starts_a_new_support_chat_and_returns_conversation_id(): void
    {
        $user = User::factory()->create();

        SupportAgent::fake([
            [
                'category'        => 'technical',
                'urgency'         => 2,
                'suggested_reply' => 'Try clearing your browser cache and logging in again.',
                'auto_resolvable' => true,
            ],
        ]);

        $response = $this->actingAs($user)
            ->postJson('/api/support/chat', [
                'message' => 'I cannot log in to my account.',
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'reply',
                'category',
                'urgency',
                'auto_resolvable',
                'conversation_id',
            ]);

        // conversation_id must be a non-empty string (UUID from RemembersConversations)
        $this->assertNotEmpty($response->json('conversation_id'));
    }

    /** @test */
    public function it_continues_an_existing_conversation(): void
    {
        $user = User::factory()->create();

        SupportAgent::fake([
            [
                'category'        => 'technical',
                'urgency'         => 2,
                'suggested_reply' => 'Let me look at that for you.',
                'auto_resolvable' => false,
            ],
        ]);

        $conversationId = 'fake-conversation-uuid';

        $response = $this->actingAs($user)
            ->postJson("/api/support/chat/{$conversationId}/continue", [
                'message' => 'I tried that already and it still does not work.',
            ]);

        $response->assertOk()
            ->assertJsonPath('conversation_id', $conversationId);
    }

    // -------------------------------------------------------------------------
    // Bulk Analysis
    // -------------------------------------------------------------------------

    /** @test */
    public function it_queues_bulk_ticket_analysis_and_returns_202(): void
    {
        $user    = User::factory()->create();
        $tickets = Ticket::factory()->count(3)->create(['user_id' => $user->id]);

        // For queued operations, we use fake() with preventStrayPrompts to ensure every dispatched prompt has a defined fake response.
        SupportAgent::fake()->preventStrayPrompts();

        $response = $this->actingAs($user)
            ->postJson('/api/support/analyze-bulk', [
                'ticket_ids' => $tickets->pluck('id')->toArray(),
            ]);

        $response->assertStatus(202)
            ->assertJsonPath('message', 'Analysis queued for 3 tickets.');

        // Assert that 3 prompts were queued (one per ticket)
        SupportAgent::assertQueued(function ($prompt) {
            return $prompt->contains('Analyze this support ticket');
        });
    }

    /** @test */
    public function it_rejects_bulk_analysis_for_tickets_belonging_to_other_users(): void
    {
        SupportAgent::fake();

        $owner   = User::factory()->create();
        $other   = User::factory()->create();
        $tickets = Ticket::factory()->count(2)->create(['user_id' => $owner->id]);

        // The other user sends ticket IDs they don't own
        $response = $this->actingAs($other)
            ->postJson('/api/support/analyze-bulk', [
                'ticket_ids' => $tickets->pluck('id')->toArray(),
            ]);

        // The controller filters by user_id, so 0 tickets are queued
        $response->assertStatus(202);
        $this->assertEquals('Analysis queued for 0 tickets.', $response->json('message'));

        SupportAgent::assertNeverQueued();
    }

    // -------------------------------------------------------------------------
    // Embeddings (Knowledge Base)
    // -------------------------------------------------------------------------

    /** @test */
    public function kb_seed_command_creates_document_records(): void
    {
        // Fake embeddings so the test doesn't call the OpenAI embeddings API
        Embeddings::fake();

        // Create a temporary markdown file in a test knowledge-base directory
        $dir = base_path('knowledge-base');
        @mkdir($dir, 0755, true);
        file_put_contents("{$dir}/test-article.md", "# Test\nThis is a test FAQ article.");

        $this->artisan('kb:seed')->assertSuccessful();

        $this->assertDatabaseHas('documents', [
            'filename' => 'test-article.md',
            'title'    => 'Test Article',
        ]);

        // Clean up
        unlink("{$dir}/test-article.md");
    }
}
