<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return ['student_id' => User::factory(), 'client_request_id' => fake()->uuid(), 'type' => 'FEEDBACK', 'status' => 'DRAFT', 'version' => 1];
    }
}
