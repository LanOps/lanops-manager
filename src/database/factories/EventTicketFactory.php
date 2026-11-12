<?php

namespace Database\Factories;

use App\Models\EventTicket;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EventTicket>
 */
class EventTicketFactory extends Factory
{
    protected $model = EventTicket::class;

    public function definition(): array
    {
        return [
            'name'       => 'Weekend Ticket',
            'type'       => 'weekend',
            'price'      => '30',
            'seatable'   => true,
            'sale_start' => null,
            'sale_end'   => null,
        ];
    }
}
