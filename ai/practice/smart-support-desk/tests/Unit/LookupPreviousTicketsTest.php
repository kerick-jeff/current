<?php

namespace Tests\Unit;

use App\Ai\Tools\LookupPreviousTickets;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

/**
 * Tests for the LookupPreviousTickets tool in isolation.
 *
 * Tools are plain PHP classes. That makes them easy to unit test without needing to spin up the full agent machinery.
 */
class LookupPreviousTicketsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_a_formatted_list_of_previous_tickets(): void
    {
        $user = User::factory()->create();

        Ticket::factory()->count(3)->create([
            'user_id'     => $user->id,
            'ai_category' => 'billing',
            'status'      => 'resolved',
        ]);

        $tool    = new LookupPreviousTickets;
        $request = new Request(['user_id' => $user->id]);
        $result  = $tool->handle($request);

        // Should return 3 lines, one per ticket
        $lines = explode("\n", trim($result));
        $this->assertCount(3, $lines);

        // Each line should contain the status and category
        foreach ($lines as $line) {
            $this->assertStringContainsString('resolved', $line);
            $this->assertStringContainsString('billing', $line);
        }
    }

    /** @test */
    public function it_returns_a_friendly_message_when_no_tickets_exist(): void
    {
        $user = User::factory()->create();

        $tool    = new LookupPreviousTickets;
        $request = new Request(['user_id' => $user->id]);
        $result  = $tool->handle($request);

        $this->assertEquals('No previous tickets found for this user.', $result);
    }

    /** @test */
    public function it_limits_results_to_five_tickets(): void
    {
        $user = User::factory()->create();

        // Create 8 tickets — tool should only return 5
        Ticket::factory()->count(8)->create(['user_id' => $user->id]);

        $tool    = new LookupPreviousTickets;
        $request = new Request(['user_id' => $user->id]);
        $result  = $tool->handle($request);

        $lines = array_filter(explode("\n", trim($result)));
        $this->assertCount(5, $lines);
    }

    /** @test */
    public function it_does_not_return_tickets_from_other_users(): void
    {
        $user  = User::factory()->create();
        $other = User::factory()->create();

        Ticket::factory()->count(3)->create(['user_id' => $other->id]);

        $tool    = new LookupPreviousTickets;
        $request = new Request(['user_id' => $user->id]);
        $result  = $tool->handle($request);

        $this->assertEquals('No previous tickets found for this user.', $result);
    }
}
