<?php

namespace Tests\Feature;

use App\Models\EventTimetable;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The Discord bot is told about new tickets, gifts and timetables through
 * spatie/laravel-webhook-server. These pin down what the bot receives.
 */
class DiscordWebhookTest extends TestCase
{
    public function testPublishingATimetableSendsASignedWebhookToTheDiscordBot()
    {
        config([
            'app.discord_bot_url'    => 'https://bot.example.test',
            'app.discord_bot_secret' => 'bot-secret',
        ]);
        Http::fake();

        $event = $this->createEventWithTicket([
            'discord_link_enabled' => true,
            'discord_channel_id'   => '1234',
        ])->event;
        $timetable = factory(EventTimetable::class)->make(['status' => 'draft']);
        $timetable->event_id = $event->id;
        $timetable->save();

        $timetable->status = 'PUBLISHED';
        $timetable->save();

        Http::assertSentCount(1);
        Http::assertSent(function (Request $request) use ($event) {
            $body = $request->body();

            return $request->url() === 'https://bot.example.test/message/channel'
                && $request->method() === 'POST'
                && $request['channel_id'] === '1234'
                && str_contains($request['message'], $event->display_name)
                && $request->header('Authorization')[0] === config('webhook-server.headers.Authorization')
                && $request->header('Signature')[0] === hash_hmac('sha256', $body, 'bot-secret');
        });
    }
}
