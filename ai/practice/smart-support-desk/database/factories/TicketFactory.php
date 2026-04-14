<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'subject' => $this->faker->sentence(6),
            'body'    => $this->faker->paragraphs(2, true),
            'status'  => $this->faker->randomElement(['open', 'in_progress', 'resolved']),
        ];
    }

    public function withAiAnalysis(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'ai_category'        => $this->faker->randomElement(['billing', 'account', 'technical']),
                'ai_urgency'         => $this->faker->numberBetween(1, 5),
                'ai_suggested_reply' => $this->faker->paragraph(),
                'ai_auto_resolvable' => $this->faker->boolean(),
            ];
        });
    }
}
