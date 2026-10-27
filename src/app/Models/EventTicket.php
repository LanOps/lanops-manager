<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventTicket extends Model
{
    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'event_tickets';

    /**
     * The most tickets of one type a user can buy in one order.
     *
     * @var int
     */
    public const MAX_PER_ORDER = 2;
    
    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = array(
        'created_at',
        'updated_at'
    );

    /*
     * Relationships
     */
    public function event()
    {
        return $this->belongsTo('App\Models\Event');
    }
    public function participants()
    {
        return $this->hasMany('App\Models\EventParticipant', 'ticket_id');
    }

    /**
     * Check there is room for this many more of this ticket, both in the
     * ticket's own stock and in the event's capacity.
     * @param  int $quantity
     * @return String|null The reason it cannot be bought, or null if it can
     */
    public function soldOutMessage($quantity)
    {
        if ($this->event->capacity < $this->event->eventParticipants()->count() + $quantity) {
            return $this->event->display_name . ' has sold out!';
        }
        if ($this->quantity != 0 && $this->quantity < $this->participants()->count() + $quantity) {
            return $this->name . ' has sold out!';
        }
        return null;
    }
}
