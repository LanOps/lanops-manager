<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\NewsTag;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Render every main page once. These catch broken views, helpers and
 * packages (Form::, seo(), sitemap, robots) during framework upgrades.
 */
class SmokeTest extends TestCase
{
    public static function publicPageProvider(): array
    {
        return [
            'home'            => ['/'],
            'events'          => ['/events'],
            'news'            => ['/news'],
            'gallery'         => ['/gallery'],
            'polls'           => ['/polls'],
            'about'           => ['/about'],
            'contact'         => ['/contact'],
            'terms'           => ['/terms'],
            'info'            => ['/info'],
            'sitemap'         => ['/sitemap'],
            'robots'          => ['/robots.txt'],
            'login'           => ['/login'],
            'register'        => ['/register/standard'],
            'forgot password' => ['/login/forgot'],
            'api events'      => ['/api/events'],
            'api upcoming'    => ['/api/events/upcoming'],
        ];
    }

    #[DataProvider('publicPageProvider')]
    public function testPublicPageRenders(string $url)
    {
        $this->createEventWithTicket();

        $this->get($url)->assertOk();
    }

    public function testEventPageRenders()
    {
        $ticket = $this->createEventWithTicket();

        $this->get('/events/' . $ticket->event->slug)
            ->assertOk()
            ->assertSee($ticket->event->display_name);
    }

    public function testNewsArticlePageRenders()
    {
        $article = factory(NewsArticle::class)->create();

        $this->get('/news/' . $article->fresh()->slug)
            ->assertOk()
            ->assertSee($article->title);
    }

    public function testNewsTagPageListsTaggedArticles()
    {
        $article = factory(NewsArticle::class)->create();
        $tag = new NewsTag();
        $tag->tag = 'Summer LAN';
        $tag->news_feed_id = $article->id;
        $tag->save();

        $this->get('/news/tags/' . $tag->fresh()->slug)
            ->assertOk()
            ->assertSee($article->title);
    }

    public function testAccountPageRenders()
    {
        $this->actingAs($this->createUser())
            ->get('/account')
            ->assertOk();
    }

    public static function adminPageProvider(): array
    {
        return [
            'dashboard' => ['/admin'],
            'events'    => ['/admin/events'],
            'users'     => ['/admin/users'],
            'news'      => ['/admin/news'],
            'gallery'   => ['/admin/gallery'],
            'polls'     => ['/admin/polls'],
            'purchases' => ['/admin/purchases'],
            'venues'    => ['/admin/venues'],
        ];
    }

    #[DataProvider('adminPageProvider')]
    public function testAdminPageRenders(string $url)
    {
        $this->createEventWithTicket();

        $this->actingAs($this->createUser(['admin' => true]))
            ->get($url)
            ->assertOk();
    }

    public function testAdminEventPageRenders()
    {
        $ticket = $this->createEventWithTicket();

        $this->actingAs($this->createUser(['admin' => true]))
            ->get('/admin/events/' . $ticket->event->slug)
            ->assertOk();
    }

    public function testAdminDashboardListsOnlyActivePolls()
    {
        $admin = $this->createUser(['admin' => true]);
        foreach (['Open poll' => null, 'Future poll' => now()->addDay(), 'Closed poll' => now()->subDay()] as $name => $end) {
            DB::table('polls')->insert([
                'name'       => $name,
                'slug'       => Str::slug($name),
                'status'     => 'PUBLISHED',
                'user_id'    => $admin->id,
                'end'        => $end,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee('Open poll')
            ->assertSee('Future poll')
            ->assertDontSee('Closed poll');
    }

    public function testAdminPagesRedirectNonAdmins()
    {
        $this->actingAs($this->createUser())
            ->get('/admin')
            ->assertRedirect('/');
    }
}
