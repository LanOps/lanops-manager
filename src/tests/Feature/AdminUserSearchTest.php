<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * Admin users list: the search box filters by username, name, email,
 * Discord name and Steam ID, and keeps the search across pages.
 */
class AdminUserSearchTest extends TestCase
{
    protected function admin()
    {
        return $this->createUser(['admin' => 1, 'username' => 'TheAdmin', 'steamid' => null]);
    }

    public function testListShowsEveryoneWithoutSearch()
    {
        $admin = $this->admin();
        $this->createUser(['username' => 'FragMaster', 'steamid' => null]);

        $this->actingAs($admin)->get('/admin/users')
            ->assertOk()
            ->assertSee('FragMaster')
            ->assertSee('TheAdmin');
    }

    public function testSearchMatchesUsernameNameEmailAndDiscord()
    {
        $admin = $this->admin();
        $this->createUser([
            'username' => 'FragMaster', 'firstname' => 'Alice', 'surname' => 'Smith',
            'email' => 'alice@example.test', 'discord_username' => 'alice_disc', 'steamid' => null,
        ]);
        $this->createUser([
            'username' => 'Camper', 'firstname' => 'Bob', 'surname' => 'Jones',
            'email' => 'bob@example.test', 'steamid' => null,
        ]);

        foreach (['fragmas', 'Alice', 'smith', 'Alice Smith', 'alice@example', 'alice_disc'] as $term) {
            $this->actingAs($admin)->get('/admin/users?search=' . urlencode($term))
                ->assertOk()
                ->assertSee('FragMaster')
                ->assertDontSee('Camper');
        }
    }

    public function testSearchMatchesExactSteamId()
    {
        $admin = $this->admin();
        $this->createUser(['username' => 'SteamPlayer', 'steamid' => '76561198000000001']);
        $this->createUser(['username' => 'OtherPlayer', 'steamid' => null]);

        $this->actingAs($admin)->get('/admin/users?search=76561198000000001')
            ->assertOk()
            ->assertSee('SteamPlayer')
            ->assertDontSee('OtherPlayer');
    }

    public function testWildcardsAreLiteral()
    {
        $admin = $this->admin();
        $this->createUser(['username' => 'Plain', 'steamid' => null]);
        $this->createUser(['username' => '100%Gamer', 'steamid' => null]);

        $this->actingAs($admin)->get('/admin/users?search=' . urlencode('%'))
            ->assertOk()
            ->assertSee('100%Gamer')
            ->assertDontSee('Plain');
    }

    public function testNoMatchesShowsMessage()
    {
        $this->actingAs($this->admin())->get('/admin/users?search=nobody-here')
            ->assertOk()
            ->assertSee('No users found.')
            ->assertSee('0 users matching');
    }

    public function testPaginationKeepsSearch()
    {
        $admin = $this->admin();
        for ($i = 1; $i <= 21; $i++) {
            $this->createUser(['username' => 'Match' . $i, 'steamid' => null]);
        }

        $this->actingAs($admin)->get('/admin/users?search=match')
            ->assertOk()
            ->assertSee('search=match', false)
            ->assertSee('21 users matching')
            ->assertDontSee('Match21');

        $this->actingAs($admin)->get('/admin/users?search=match&page=2')
            ->assertOk()
            ->assertSee('Match21')
            ->assertDontSee('Match20');
    }

    public function testNonAdminCannotSearch()
    {
        $this->actingAs($this->createUser(['steamid' => null]))->get('/admin/users?search=a')
            ->assertRedirect();
    }
}
