<?php

namespace App\Libraries;

use Illuminate\Http\Client\ConnectionException;
use DB;
use Auth;
use App\Models\User;
use \Carbon\Carbon as Carbon;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class Helpers
{
    // TODO - refactor - eg getGameSelectArray - specifially the selectArray part

    // public static function getGameSelectArray($publicOnly = true)
    // {
    //     $return[0] = 'None';
    //     foreach (Game::where('public', $publicOnly)->orderBy('name', 'ASC')->get() as $game) {
    //         $return[$game->id] = $game->name;
    //     }
    //     return $return;
    // }
    /**
     * Get Venues
     * @param  boolean $obj Return as Object
     * @return Array|Object
     */
    public static function getVenues($obj = false)
    {
        $venues = \App\Models\EventVenue::all();
        $return = array();
        foreach ($venues as $venue) {
                $return[$venue->id] = $venue->display_name;
        }
        if (!$obj) {
            $return[] = 'None';
        }
        if ($obj) {
            return json_decode(json_encode($return), false);
        }
        return $return;
    }

    /**
     * Get Events
     * @param  string  $order
     * @param  integer $limit
     * @param  boolean $obj   Return as Object
     * @return Array|Object
     */
    public static function getEvents($order = 'DESC', $limit = 0, $obj = false)
    {
        $return = array();
        if ($limit != 0) {
            $events = \App\Models\Event::orderBy('start', $order)->paginate($limit);
        } else {
            $events = \App\Models\Event::orderBy('start', 'DESC')->get();
        }
        $return = array();
        foreach ($events as $event) {
            $return[$event->id] = $event;
        }
        if ($obj) {
            return json_decode(json_encode($return), false);
        }
        return $return;
    }

    /**
     * Get Event Names
     * @param  string  $order
     * @param  integer $limit
     * @param  boolean $future
     * @param  boolean $obj   Return as Object
     * @return Array|Object
     */
    public static function getEventNames($order = 'DESC', $limit = 0, $future = false, $obj = false)
    {
        $return = array();
        if ($limit != 0) {
            if ($future) {
                $events = \App\Models\Event::where('end', '>=', date('Y-m-d'))->orderBy('start', $order)->paginate($limit);
            } else {
                $events = \App\Models\Event::orderBy('start', $order)->paginate($limit);
            }
        } else {
            if ($future) {
                $events = \App\Models\Event::where('end', '>=', date('Y-m-d'))->orderBy('start', 'DESC')->get();
            } else {
                $events = \App\Models\Event::orderBy('start', 'DESC')->get();
            }
        }
        if (!$obj) {
            $return[] = 'None';
        }
        foreach ($events as $event) {
            $return[$event->id] = $event->display_name;
        }
        if ($obj) {
            return json_decode(json_encode($return), false);
        }
        return $return;
    }

    /**
     * Get Total Events Count
     * @return Integer
     */
    public static function getEventTotal()
    {
        $events = \App\Models\Event::count();
        // Historical before this site
        return 23 + $events;
    }

    /**
     * Get the event that ends soonest, worked out once per request
     * (the navigation asks for it on every page, several times)
     * @return \App\Models\Event|null
     */
    public static function getNextEvent()
    {
        // Kept on the request, so it is fresh for every request (and every request in a test)
        $attributes = request()->attributes;
        if (! $attributes->has('next_event')) {
            $attributes->set('next_event', \App\Models\Event::where('end', '>=', Carbon::now())
                ->orderBy(DB::raw('ABS(DATEDIFF(events.end, NOW()))'))
                ->first());
        }
        return $attributes->get('next_event');
    }

    /**
     * Get Next Event Name
     * @return String
     */
    public static function getNextEventName()
    {
        if ($event = self::getNextEvent()) {
            if ($event->status == 'DRAFT' || $event->status == 'PREVIEW') {
                return $event->display_name . ' - ' . $event->status;
            }
            return $event->display_name;
        }
        return 'Coming soon...';
    }

    /**
     * Get Next Event Slug
     * @return String
     */
    public static function getNextEventSlug()
    {
        if ($event = self::getNextEvent()) {
            return $event->slug;
        }
        return '#';
    }


    /**
     * Get Next Event Description
     * @return String
     */
    public static function getNextEventDesc()
    {
        if ($event = self::getNextEvent()) {
            return $event->desc_long;
        }
        return 'Coming soon...';
    }

    /**
     * Get Next Event Start Date
     * @return String
     */
    public static function getNextEventStartDate()
    {
        if ($event = self::getNextEvent()) {
            return date("d-m-Y H:i", strtotime($event->start));
        }
        return 'Coming soon...';
    }

    /**
     * Get Next Event End Date
     * @return String
     */
    public static function getNextEventEndDate()
    {
        if ($event = self::getNextEvent()) {
            return date("d-m-Y H:i", strtotime($event->end));
        }
        return 'Coming soon...';
    }

    /**
     * Get Total Event Participants Count
     * @return Integer
     */
    public static function getEventParticipantTotal()
    {
        $participants = \App\Models\EventParticipant::count();
        // Historical before this site
        return 686 + $participants;
    }

    /**
     * Get Basket Total
     * @param  $basket
     * @return Integer
     */
    public static function getBasketTotal($basket)
    {
        $return = 0;
        foreach ($basket as $ticket_id => $quantity) {
            $ticket = \App\Models\EventTicket::where('id', $ticket_id)->first();
            $return += ($ticket->price * $quantity);
        }
        return $return;
    }

    /**
     * array_key_exists with regex
     * @param  $pattern
     * @param  $array
     * @return Integer
     */
    public static function pregArrayKeyExists($pattern, $array)
    {
        $keys = array_keys($array);
        return (int) preg_grep($pattern, $keys);
    }

    /**
     * Format Shopping Basket into Readable format
     * @param $itemId
     * @return Boolean
     */
    public static function formatBasket($basket, ?User $user = null, $referralDiscountAmountOverride = null, $skipAvailableReferralsCheck = false)
    {
        if (!$user) {
            $user = Auth::user();
        }
        if (!$referralDiscountAmountOverride) {
            $referralDiscountAmountOverride = config('app.refer_a_friend_discount');
        }
        if (array_key_exists('tickets', $basket)) {
            $formattedBasket = \App\Models\EventTicket::whereIn('id', array_keys($basket['tickets']))->get();
        }
        if (!$formattedBasket) {
            return false;
        }
        $formattedBasket->total = 0;
        $formattedBasket->total_before_discounts = 0;
        $formattedBasket->referral_discount_total = 0;
        $formattedBasket->allow_payment = true;
        $formattedBasket->referral_code = null;
        $formattedBasket->referral_used = false;
        foreach ($formattedBasket as $item) {
            if (array_key_exists('tickets', $basket)) {
                $item->quantity = $basket['tickets'][$item->id];
                $formattedBasket->total += $item->price * $item->quantity;
                $formattedBasket->total_before_discounts += $item->price * $item->quantity;
            }
            if ($item->price == null || $item->price < 0) {
                $formattedBasket->allow_payment = false;
            }
        }
        if (array_key_exists('referral_discount', $basket) && $basket['referral_discount'] && ($user->getAvailableReferralPurchase() || $skipAvailableReferralsCheck)) {
            $formattedBasket->total -= $referralDiscountAmountOverride;
            $formattedBasket->referral_discount_total += $referralDiscountAmountOverride;
            $formattedBasket->referral_used = true;
        }
        if (array_key_exists('codes', $basket)) {
            if (array_key_exists('referral', $basket['codes']) && User::isValidReferralCode($basket['codes']['referral'], Auth::user())) {
                $formattedBasket->referral_code = $basket['codes']['referral'];
            }
        }
        return $formattedBasket;
    }

    /**
     * Get Card Expiry Month Dates
     * @return array
     */
    public static function getCardExpiryMonthDates()
    {
        $return = array();
        for ($i=1; $i<=12; $i++) {
            $date = $i;
            $return[$date] = $date;
        }
        return $return;
    }

    /**
     * Get Card Expiry Year Dates
     * @return array
     */
    public static function getCardExpiryYearDates()
    {
        $return = array();
        for ($i=(int)date('y'); $i<=99; $i++) {
            $date = $i;
            $return[$date] = $date;
        }
        return $return;
    }

    /**
     * Get Countries for Select
     * @return array
     */
    /**
     * Countries a venue can be in, by ISO code. Kept here rather than fetched
     * from a web service on every save; add to it alongside getSelectCountries.
     * @return array
     */
    public static function getCountries()
    {
        return [
            'GB' => 'United Kingdom',
        ];
    }

    public static function getSelectCountries()
    {
        $countriesArray = [
            "United Kingdom",
        ];
        return $countriesArray;
    }

    /**
     * Steam profiles already looked up in this request
     * @var array<string, array|false>
     */
    private static array $steamProfiles = [];

    public static function getSteamProfile(?string $steamId): ?array
    {
        if (! $steamId) {
            return null;
        }
        return self::getSteamProfiles([$steamId])[$steamId] ?? null;
    }

    /**
     * Look up several Steam profiles with one Steam API request.
     * Call it before a list that shows Steam names, so each row reads from the cache.
     * @param  array $steamIds
     * @return array<string, array> profiles keyed by Steam id; ids with no profile are left out
     */
    public static function getSteamProfiles(array $steamIds): array
    {
        $steamIds = array_values(array_unique(array_filter(
            array_map('strval', array_filter($steamIds)),
            fn ($steamId) => preg_match('/^\d{17}$/', $steamId)
        )));

        $missing = [];
        foreach ($steamIds as $steamId) {
            if (! array_key_exists($steamId, self::$steamProfiles)) {
                // false is a cached "no profile", so a failed lookup isn't retried on every page view
                $cached = Cache::get("steam_profile_{$steamId}");
                if ($cached === null) {
                    $missing[] = $steamId;
                } else {
                    self::$steamProfiles[$steamId] = $cached;
                }
            }
        }

        // GetPlayerSummaries takes up to 100 ids per request
        foreach (array_chunk($missing, 100) as $chunk) {
            $players = self::fetchSteamPlayers($chunk);
            foreach ($chunk as $steamId) {
                if ($players === null) {
                    // Steam is down or slow: don't cache, but don't ask again in this request
                    self::$steamProfiles[$steamId] = false;
                    continue;
                }
                $profile = $players[$steamId] ?? false;
                Cache::put(
                    "steam_profile_{$steamId}",
                    $profile,
                    $profile ? now()->addDays(7) : now()->addHour()
                );
                self::$steamProfiles[$steamId] = $profile;
            }
        }

        $profiles = [];
        foreach ($steamIds as $steamId) {
            if (self::$steamProfiles[$steamId] ?? false) {
                $profiles[$steamId] = self::$steamProfiles[$steamId];
            }
        }
        return $profiles;
    }

    /**
     * Forget profiles looked up in this request (for tests)
     */
    public static function flushSteamProfiles(): void
    {
        self::$steamProfiles = [];
    }

    /**
     * @param  array $steamIds at most 100
     * @return array<string, array>|null profiles keyed by Steam id, or null if Steam could not be reached
     */
    private static function fetchSteamPlayers(array $steamIds): ?array
    {
        // Steam being slow or down must not break the pages that show profiles
        try {
            $response = Http::timeout(5)->get(
                'https://api.steampowered.com/ISteamUser/GetPlayerSummaries/v0002/',
                [
                    'key' => config('services.steam.client_secret'),
                    'steamids' => implode(',', $steamIds),
                ]
            );
        } catch (ConnectionException $e) {
            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $players = [];
        foreach ($response->json('response.players') ?? [] as $player) {
            $steamId = $player['steamid'] ?? (count($steamIds) === 1 ? $steamIds[0] : null);
            if ($steamId === null) {
                continue;
            }
            $players[(string) $steamId] = [
                'steam_id' => $steamId,
                'name' => $player['personaname'] ?? null,
                'avatar' => $player['avatarfull'] ?? ($player['avatarmedium'] ?? $player['avatar'] ?? null),
                'profile_url' => $player['profileurl'] ?? null,
            ];
        }
        return $players;
    }
}
