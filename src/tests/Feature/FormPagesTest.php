<?php

namespace Tests\Feature;

use App\Models\EventSeatingPlan;
use App\Models\EventTimetable;
use App\Models\NewsArticle;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Render every page that holds a form, with enough data that most of the
 * forms on it show up. Guards the views' form markup (html()->...).
 */
class FormPagesTest extends TestCase
{
    protected User $admin;
    protected User $member;
    protected array $slugs = [];

    protected function setUp(): void
    {
        parent::setUp();

        // admin poll and tournament pages build links from these
        $_SERVER['REQUEST_SCHEME'] = 'http';
        $_SERVER['HTTP_HOST'] = 'localhost';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST']);

        parent::tearDown();
    }

    protected function seedSite(): void
    {
        $this->admin = $this->createUser(['admin' => true]);
        $this->member = $this->createUser();
        $ticket = $this->createEventWithTicket();
        $event = $ticket->event;
        $now = now();

        $participantId = DB::table('event_participants')->insertGetId([
            'user_id' => $this->member->id, 'event_id' => $event->id, 'ticket_id' => $ticket->id,
            'transferred_event_id' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $seatingPlan = factory(EventSeatingPlan::class)->make();
        $seatingPlan->forceFill(['event_id' => $event->id, 'name_short' => 'Main'])->save();
        DB::table('event_seating')->insert([
            'seat' => 'A1', 'event_seating_plan_id' => $seatingPlan->id, 'event_participant_id' => $participantId,
            'gifted' => 0, 'created_at' => $now, 'updated_at' => $now,
        ]);

        $timetable = factory(EventTimetable::class)->make();
        $timetable->forceFill(['event_id' => $event->id])->save();
        DB::table('event_timetable_data')->insert([
            'event_timetable_id' => $timetable->id, 'name' => 'Doors open', 'start_time' => $event->start,
            'desc' => 'Welcome', 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('event_information')->insert([
            'event_id' => $event->id, 'title' => 'Parking', 'text' => '<p>Park &amp; ride</p>', 'order' => 1,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        foreach (['Solo Cup' => 0, 'Team Cup' => 2] as $name => $teamSize) {
            $tournamentId = DB::table('tournaments')->insertGetId([
                'name' => $name, 'slug' => strtolower(str_replace(' ', '-', $name)), 'event_id' => $event->id,
                'team_size' => $teamSize, 'status' => 'OPEN', 'created_at' => $now, 'updated_at' => $now,
            ]);
            $teamId = $teamSize ? DB::table('tournament_teams')->insertGetId([
                'tournament_id' => $tournamentId, 'name' => 'Red Team', 'created_at' => $now, 'updated_at' => $now,
            ]) : null;
            DB::table('tournament_participants')->insert([
                'user_id' => $this->member->id, 'tournament_id' => $tournamentId, 'tournament_team_id' => $teamId,
                'created_at' => $now, 'updated_at' => $now,
            ]);
        }

        $pollId = DB::table('polls')->insertGetId([
            'name' => 'Next game', 'slug' => 'next-game', 'description' => 'Pick one', 'status' => 'PUBLISHED',
            'allow_options_multi' => 1, 'allow_options_user' => 1, 'user_id' => $this->admin->id,
            'event_id' => $event->id, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('poll_options')->insert([
            'name' => 'Quake', 'poll_id' => $pollId, 'user_id' => $this->admin->id, 'created_at' => $now, 'updated_at' => $now,
        ]);

        DB::table('gallery_albums')->insert([
            'name' => 'Summer LAN', 'slug' => 'summer-lan', 'description' => 'Photos', 'event_id' => $event->id,
            'status' => 'PUBLISHED', 'created_at' => $now, 'updated_at' => $now,
        ]);

        $article = factory(NewsArticle::class)->create(['user_id' => $this->admin->id]);
        DB::table('news_feed_comments')->insert([
            'comment' => 'Nice', 'reviewed' => 1, 'reviewed_by' => $this->admin->id, 'approved' => 1,
            'approved_by' => $this->admin->id, 'news_feed_id' => $article->id, 'user_id' => $this->member->id,
            'created_at' => $now, 'updated_at' => $now,
        ]);

        $this->slugs = [
            '{event}' => $event->slug,
            '{venue}' => $event->venue->slug,
            '{ticket}' => $ticket->id,
            '{participant}' => $participantId,
            '{seatingPlan}' => $seatingPlan->fresh()->slug,
            '{timetable}' => $timetable->fresh()->slug,
            '{member}' => $this->member->id,
            '{article}' => $article->fresh()->slug,
        ];
    }

    public static function pageProvider(): array
    {
        return [
            'event (member)'         => ['member', '/events/{event}'],
            'tournaments (member)'   => ['member', '/tournaments'],
            'account'                => ['member', '/account'],
            'news article (member)'  => ['member', '/news/{article}'],
            'poll (member)'          => ['member', '/polls/next-game'],
            'register'               => ['guest', '/register/standard'],
            'forgot password'        => ['guest', '/login/forgot'],
            'admin events'           => ['admin', '/admin/events'],
            'admin event'            => ['admin', '/admin/events/{event}'],
            'admin seating'          => ['admin', '/admin/events/{event}/seating'],
            'admin seating plan'     => ['admin', '/admin/events/{event}/seating/{seatingPlan}'],
            'admin timetables'       => ['admin', '/admin/events/{event}/timetables'],
            'admin timetable'        => ['admin', '/admin/events/{event}/timetables/{timetable}'],
            'admin participant'      => ['admin', '/admin/events/{event}/participants/{participant}'],
            'admin tickets'          => ['admin', '/admin/events/{event}/tickets'],
            'admin ticket'           => ['admin', '/admin/events/{event}/tickets/{ticket}'],
            'admin venues'           => ['admin', '/admin/venues'],
            'admin venue'            => ['admin', '/admin/venues/{venue}'],
            'admin gallery'          => ['admin', '/admin/gallery'],
            'admin album'            => ['admin', '/admin/gallery/summer-lan'],
            'admin user'             => ['admin', '/admin/users/{member}'],
            'admin news'             => ['admin', '/admin/news'],
            'admin news article'     => ['admin', '/admin/news/{article}'],
            'admin polls'            => ['admin', '/admin/polls'],
            'admin poll'             => ['admin', '/admin/polls/next-game'],
            'admin tournaments'      => ['admin', '/admin/tournaments'],
            'admin tournament'       => ['admin', '/admin/tournaments/solo-cup'],
            'admin team tournament'  => ['admin', '/admin/tournaments/team-cup'],
        ];
    }

    protected function fetch(string $as, string $url): string
    {
        $this->withoutExceptionHandling();
        if ($as !== 'guest') {
            $this->actingAs($as === 'admin' ? $this->admin : $this->member);
        }

        return $this->get(strtr($url, $this->slugs))->assertOk()->getContent();
    }

    #[DataProvider('pageProvider')]
    public function testPageWithFormsRenders(string $as, string $url)
    {
        $this->seedSite();

        $html = $this->fetch($as, $url);

        $this->assertStringContainsString('name="_token"', $html);
        $this->assertStringNotContainsString('Form::', $html);
    }

    public function testTextareaEscapesItsContents()
    {
        $this->seedSite();
        $article = factory(NewsArticle::class)->create([
            'user_id' => $this->admin->id,
            'article' => '<p>Fish &amp; chips</p></textarea>',
        ]);

        $this->actingAs($this->admin)->get('/admin/news/' . $article->fresh()->slug)
            ->assertOk()
            ->assertSee('&lt;p&gt;Fish &amp;amp; chips&lt;/p&gt;&lt;/textarea&gt;</textarea>', false);
    }
}
