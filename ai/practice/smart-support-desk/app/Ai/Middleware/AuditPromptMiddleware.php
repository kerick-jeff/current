<?php

namespace App\Ai\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;

/**
 * Middleware that runs around every agent prompt.
 *
 * It records:
 *   - Which agent and provider handled the prompt
 *   - The raw prompt text
 *   - Token usage (prompt + completion)
 *   - Total round-trip time in milliseconds
 *
 * This gives us an audit trail + a foundation for cost tracking.
 */
class AuditPromptMiddleware
{
    /**
     * Handle the incoming prompt.
     */
    public function handle(AgentPrompt $prompt, Closure $next)
    {
        $startTime = microtime(true);

        // Pass control to the agent (and any subsequent middleware)
        $response = $next($prompt);

        // The `then` callback fires once the full response is available, whether the call was synchronous or streamed.
        return $response->then(function (AgentResponse $agentResponse) use ($prompt, $startTime) {
            $durationMs = round((microtime(true) - $startTime) * 1000, 2);

            AuditLog::create([
                'user_id'           => Auth::id(),
                'agent'             => get_class($prompt->agent),
                'provider'          => $agentResponse->provider ?? 'unknown',
                'model'             => $agentResponse->model ?? null,
                'prompt'            => $prompt->prompt,
                'prompt_tokens'     => $agentResponse->usage?->promptTokens,
                'completion_tokens' => $agentResponse->usage?->completionTokens,
                'reasoning_tokens'  => $agentResponse->usage?->reasoningTokens,
                'duration_ms'       => $durationMs,
            ]);
        });
    }
}
