<?php

namespace Tests\Feature;

use App\Models\EventTimetable;
use App\Models\EventTimetableData;
use Carbon\Carbon;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * bot:timetable-updates posts "due to start" reminders to the event's Discord
 * channel. The event role must be a real Discord mention (<@&id>), not "@id".
 */
class BotTimetableUpdatesTest extends TestCase
{
    private function setUpEventWithSlotInAnHour(?string $roleId): void
    {
        config([
            'app.discord_bot_url'    => 'https://bot.example.test',
            'app.discord_bot_secret' => 'bot-secret',
        ]);

        $event = $this->createEventWithTicket([
            'start'                => Carbon::now()->subMinutes(30)->format('Y-m-d H:i:s'),
            'end'                  => Carbon::now()->addDay()->format('Y-m-d H:i:s'),
            'discord_link_enabled' => true,
            'discord_channel_id'   => '1234',
            'discord_role_id'      => $roleId,
        ])->event;

        $timetable = EventTimetable::factory()->make(['status' => 'PUBLISHED']);
        $timetable->event_id = $event->id;
        $timetable->saveQuietly();

        $slot = new EventTimetableData();
        $slot->forceFill([
            'event_timetable_id' => $timetable->id,
            'start_time'         => Carbon::now()->addSeconds(59 * 60 + 30)->format('Y-m-d H:i:s'),
            'name'               => 'LAN Start',
        ])->save();

        Http::fake();
    }

    public function testAnnouncementMentionsTheEventRoleAsADiscordRoleMention()
    {
        $this->setUpEventWithSlotInAnHour('1554224701574750228');

        $this->artisan('bot:timetable-updates')->assertSuccessful();

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request) =>
            $request->url() === 'https://bot.example.test/message/channel'
            && $request['channel_id'] === '1234'
            && $request['message'] === 'TIMETABLE ANNOUNCEMENT: LAN Start - Due to start in 1 hour! - <@&1554224701574750228>');
    }

    public function testAnnouncementLeavesOutTheMentionWhenTheEventHasNoRole()
    {
        $this->setUpEventWithSlotInAnHour(null);

        $this->artisan('bot:timetable-updates')->assertSuccessful();

        Http::assertSent(fn (Request $request) =>
            $request['message'] === 'TIMETABLE ANNOUNCEMENT: LAN Start - Due to start in 1 hour!');
    }
}
