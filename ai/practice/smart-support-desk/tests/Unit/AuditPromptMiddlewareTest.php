<?php

namespace Tests\Unit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Smoke tests for the middleware structure.
 *
 * The full integration test happens implicitly in SupportAgentTest because the middleware is registered on the agent.
 * Here we just verify the model and structure are correct.
 */
class AuditPromptMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function audit_log_model_is_fillable_with_expected_fields(): void
    {
        $user = User::factory()->create();

        $log = AuditLog::create([
            'user_id'           => $user->id,
            'agent'             => 'App\\Ai\\Agents\\SupportAgent',
            'provider'          => 'openai',
            'model'             => 'gpt-4o',
            'prompt'            => 'Analyze this ticket.',
            'prompt_tokens'     => 120,
            'completion_tokens' => 340,
            'total_tokens'      => 460,
            'duration_ms'       => 1234.56,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'agent'       => 'App\\Ai\\Agents\\SupportAgent',
            'provider'    => 'openai',
            'total_tokens' => 460,
        ]);

        $this->assertEquals(1234.56, $log->duration_ms);
    }

    /** @test */
    public function audit_log_can_be_created_without_a_user(): void
    {
        // Unauthenticated calls (for instance, from Artisan commands) should not crash
        AuditLog::create([
            'user_id'  => null,
            'agent'    => 'App\\Ai\\Agents\\SupportAgent',
            'provider' => 'anthropic',
            'prompt'   => 'Seed run.',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'user_id'  => null,
            'provider' => 'anthropic',
        ]);
    }
}
