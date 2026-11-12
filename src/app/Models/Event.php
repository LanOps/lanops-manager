<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Concerns\HidesFromNonAdmins;
use Auth;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

use Cviebrock\EloquentSluggable\Sluggable;

class Event extends Model
{
    use HasFactory;
    use HidesFromNonAdmins;
    use Sluggable;

    /**
     * The name of the table.
     *
     * @var string
     */
    protected $table = 'events';
    
    protected $casts = ['deleted_at' => 'datetime'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'display_name',
        'status',
        'start',
        'end',
        'type'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = array(
        'created_at',
        'updated_at',
        'private_key',
    );

    /**
     * Session key holding the Private events this visitor has opened the secret link for,
     * as [event id => key], so the rest of the site (tickets, checkout, seating) lets them in too.
     */
    public const PRIVATE_SESSION_KEY = 'private_events';

    public static $typeLan = 'LAN';

    public static $typeTabletop = 'TABLETOP';

    protected static function boot()
    {
        parent::boot();

        static::addNonAdminScope('statusDraft', function (Builder $builder) {
            $builder->where('status', '!=', 'DRAFT');
        });
        static::addNonAdminScope('statusPublished', function (Builder $builder) {
            $builder->where(function (Builder $query) {
                $query->where('status', 'PUBLISHED')
                    ->orWhere(function (Builder $query) {
                        Event::whereAccessiblePrivate($query);
                    });
            });
        });

        static::saving(function (Event $event) {
            // A Private event always has a secret link to share
            if (strtoupper((string) $event->status) == 'PRIVATE' && empty($event->private_key)) {
                $event->private_key = static::generatePrivateKey();
            }
        });
    }

    /**
     * Limit a query to the Private events the current visitor may see: ones they have a ticket
     * for, and ones they have opened the (still current) secret link for in this session.
     * @param  Builder $query
     * @return void
     */
    protected static function whereAccessiblePrivate(Builder $query)
    {
        $query->where('status', 'PRIVATE')->where(function (Builder $query) {
            // Never matches, so a visitor with neither a ticket nor a link sees nothing
            $query->whereRaw('1 = 0');
            if ($userId = Auth::id()) {
                $query->orWhereIn(
                    'events.id',
                    EventParticipant::select('event_id')->where('user_id', $userId)
                );
            }
            foreach (static::unlockedPrivateKeys() as $eventId => $key) {
                // The key is checked too, so a regenerated link shuts out old sessions
                $query->orWhere(function (Builder $query) use ($eventId, $key) {
                    $query->where('events.id', (int) $eventId)->where('events.private_key', (string) $key);
                });
            }
        });
    }

    /**
     * The Private event keys stored in this visitor's session
     * @return array
     */
    protected static function unlockedPrivateKeys()
    {
        $request = request();
        if (!$request || !$request->hasSession()) {
            return [];
        }
        $keys = $request->session()->get(static::PRIVATE_SESSION_KEY, []);
        return is_array($keys) ? $keys : [];
    }

    /**
     * Let this visitor into a Private event for the rest of their session
     * @return void
     */
    public function unlockForSession()
    {
        $keys = static::unlockedPrivateKeys();
        $keys[$this->id] = $this->private_key;
        request()->session()->put(static::PRIVATE_SESSION_KEY, $keys);
    }

    /**
     * Find the Private event a secret link belongs to
     * @param  string $key
     * @return Event|null
     */
    public static function findByPrivateKey($key)
    {
        if (!is_string($key) || strlen($key) < 16) {
            return null;
        }
        $event = static::withoutGlobalScopes()->where('status', 'PRIVATE')->where('private_key', $key)->first();
        // The database match may ignore case, so check the key exactly as well
        if (!$event || !hash_equals((string) $event->private_key, $key)) {
            return null;
        }
        return $event;
    }

    /**
     * @return string
     */
    public static function generatePrivateKey()
    {
        return Str::random(32);
    }

    /**
     * The secret link for a Private event
     * @return string|null
     */
    public function getPrivateUrl()
    {
        if ($this->status != 'PRIVATE' || empty($this->private_key)) {
            return null;
        }
        return url('/e/' . $this->private_key);
    }

    /**
     * Events that belong in public lists (events page, home page, menus, API).
     * Private events are left out for everyone, admins included: they are only
     * reached through their secret link, a ticket, or the admin pages.
     * @param  Builder $query
     * @return Builder
     */
    public function scopeListed(Builder $query)
    {
        return $query->where('events.status', '!=', 'PRIVATE');
    }

    /*
     * Relationships
     */
    public function eventParticipants()
    {
        return $this->hasMany('App\Models\EventParticipant');
    }
    public function timetables()
    {
        return $this->hasMany('App\Models\EventTimetable');
    }
    public function tickets()
    {
        return $this->hasMany('App\Models\EventTicket');
    }
    public function seatingPlans()
    {
        return $this->hasMany('App\Models\EventSeatingPlan');
    }
    public function information()
    {
        return $this->hasMany('App\Models\EventInformation');
    }
    public function venue()
    {
        return $this->belongsTo('App\Models\EventVenue', 'event_venue_id');
    }
    public function galleries()
    {
        return $this->hasMany('App\Models\GalleryAlbum');
    }
    public function polls()
    {
        return $this->hasMany('App\Models\Poll', 'event_id');
    }

    public function tournaments()
    {
        return $this->hasMany('App\Models\Tournament');
    }

    /**
     * Return the sluggable configuration array for this model.
     *
     * @return array
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'nice_name',
                // Renaming an event keeps its address, so links already shared keep working
                'onUpdate' => false
            ]
        ];
    }
    
    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Get Seat
     * @param  $seatingPlanId
     * @param  $seat
     * @return EventSeating
     */
    public function getSeat($seatingPlanId, $seat)
    {
        $seatingPlan = $this->seatingPlans()->find($seatingPlanId);
        return $seatingPlan->seats()->where('seat', ucwords($seat))->first();
    }
    
    /**
     * Get Event Participant
     * @param  $userId
     * @return EventParticipant
     */
    public function getEventParticipant($userId = null)
    {
        if ($userId == null) {
            $userId = Auth::id();
        }
        return $this->eventParticipants()->where('user_id', $userId)->first();
    }

    /**
     * Get Total Ticket Sales
     * @return Integer
     */
    public function getTicketSalesCount()
    {
        $total = 0;
        $discountedPurchases = [];
        foreach ($this->eventParticipants as $participant) {
            if ($participant->purchase && $participant->ticket) {
                $total += $participant->ticket->price;
                // A referral discount is taken off the whole purchase, not off each ticket in it
                if (!in_array($participant->purchase_id, $discountedPurchases)) {
                    $total -= $participant->purchase->referral_discount_total;
                    $discountedPurchases[] = $participant->purchase_id;
                }
            }
        }
        return $total;
    }

    /**
     * Get Total Seated
     * @return Integer
     */
    public function getSeatedCount()
    {
        $total = 0;
        foreach ($this->seatingPlans as $seatingPlan) {
            $total += $seatingPlan->getSeatedCount();
        }
        return $total;
    }

    /**
     * Get Seating Capacity
     * @return Integer
     */
    public function getSeatingCapacity()
    {
        $total = 0;
        foreach ($this->seatingPlans as $seatingPlan) {
            $total += $seatingPlan->getCapacity();
        }
        return $total;
    }

    /**
     * Get Event Participants
     * @param  boolean $obj
     * @return Array|Boolean
     */
    public function getParticipants($obj = false)
    {
        $return = array();
        foreach ($this->eventParticipants as $participant) {
            if (($participant->staff || $participant->free) || @$participant->ticket->seatable) {
                $seat = 'NOT SEATED';
                if (!empty($participant->seat)) {
                    $seat = $participant->seat->seat;
                }
                $text = $participant->user->username . ' - ' . $seat;
                if ($participant->staff) {
                    $text = $participant->user->username . ' - ' . $seat . ' - Staff Ticket';
                }
                if ($participant->free) {
                    $text = $participant->user->username . ' - ' . $seat . ' - Free Ticket';
                }
                $return[$participant->id] = $text;
            }
        }
        if ($obj) {
            return json_decode(json_encode($return), false);
        }
        return $return;
    }

    /**
     * Get Timetable Data Count
     * @return Integer
     */
    public function getTimetableDataCount()
    {
        $total = 0;
        foreach ($this->timetables as $timetable) {
            $total += $timetable->data()->count();
        }
        return $total;
    }

    /**
     * Get Cheapest Ticket
     * @return Object
     */
    public function getCheapestTicket()
    {
        return $this->tickets->where('price', '!==', null)->min('price');
    }

    public static function getTypeArray()
    {
        return [
            Event::$typeLan         => Event::$typeLan,
            Event::$typeTabletop    => Event::$typeTabletop,
        ];
    }
}
