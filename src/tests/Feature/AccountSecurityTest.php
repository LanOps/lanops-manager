<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Account changes and sign-up paths that must not be possible with only a
 * hijacked session or a hand-made form.
 */
class AccountSecurityTest extends TestCase
{
    protected function userWithPassword(array $attributes = []): User
    {
        return $this->createUser(array_merge(['password' => Hash::make('old-password')], $attributes));
    }

    public function testNameCanBeChangedWithoutThePassword()
    {
        $user = $this->userWithPassword();

        $this->actingAs($user)
            ->post('/account', ['firstname' => 'New', 'surname' => 'Name', 'email' => $user->email])
            ->assertSessionHasNoErrors();

        $this->assertSame('New', $user->fresh()->firstname);
    }

    public function testChangingEmailNeedsTheCurrentPassword()
    {
        $user = $this->userWithPassword();

        $this->actingAs($user)
            ->post('/account', ['email' => 'new@example.com'])
            ->assertSessionHasErrors('current_password');

        $this->assertNotSame('new@example.com', $user->fresh()->email);
    }

    public function testChangedEmailMustBeVerifiedAgain()
    {
        Notification::fake();
        $user = $this->userWithPassword();

        $this->actingAs($user)
            ->post('/account', ['email' => 'new@example.com', 'current_password' => 'old-password'])
            ->assertSessionHasNoErrors();

        $user->refresh();
        $this->assertSame('new@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function testEmailInUseIsAValidationErrorNotACrash()
    {
        $other = $this->createUser();
        $user = $this->userWithPassword();

        $this->actingAs($user)
            ->post('/account', ['email' => $other->email, 'current_password' => 'old-password'])
            ->assertSessionHasErrors('email');
    }

    public function testChangingPasswordNeedsTheCurrentPassword()
    {
        $user = $this->userWithPassword();

        $this->actingAs($user)
            ->post('/account', ['password1' => 'new-password', 'password2' => 'new-password', 'current_password' => 'wrong'])
            ->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('old-password', $user->fresh()->password));

        $this->actingAs($user)
            ->post('/account', ['password1' => 'new-password', 'password2' => 'new-password', 'current_password' => 'old-password'])
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));
    }

    public function testDiscordLinkNeedsTheStateFromTheAccountPage()
    {
        Http::fake();
        $user = $this->createUser();

        $this->actingAs($user)
            ->withSession(['discordoauth' => 'expected-state'])
            ->get('/account/discord/callback?code=abc&state=attacker-state')
            ->assertRedirect('account')
            ->assertSessionHas('alert-danger');

        Http::assertNothingSent();
    }

    public function testDiscordLinkWithTheRightStateLinksTheAccount()
    {
        Http::fake([
            'discord.com/api/oauth2/token' => Http::response(['access_token' => 'token']),
            'discord.com/api/users/@me'    => Http::response(['id' => '42', 'global_name' => 'Disco', 'avatar' => 'abc']),
        ]);
        $user = $this->createUser();

        $this->actingAs($user)
            ->withSession(['discordoauth' => 'expected-state'])
            ->get('/account/discord/callback?code=abc&state=expected-state')
            ->assertRedirect('account');

        $this->assertSame('42', $user->fresh()->discord_id);
    }

    public function testSteamRegistrationNeedsASteamLogin()
    {
        $this->post('/register/steam', [
            'firstname' => 'Steam',
            'surname'   => 'Person',
            'username'  => 'steamperson',
            'steamid'   => '76561198000000004',
            'avatar'    => 'https://avatars.example/new.jpg',
        ])->assertRedirect('/login');

        $this->assertNull(User::where('steamid', '76561198000000004')->first());
    }

    public function testSteamRegistrationIgnoresASteamIdInTheForm()
    {
        $this->withSession(['user' => ['steamid' => '76561198000000004', 'avatar' => 'https://avatars.example/a.jpg']])
            ->post('/register/steam', [
                'firstname' => 'Steam',
                'surname'   => 'Person',
                'username'  => 'steamperson',
                'steamid'   => '76561198000000099',
                'avatar'    => 'https://evil.example/pixel.gif',
            ])->assertRedirect('/account');

        $user = User::where('username', 'steamperson')->firstOrFail();
        $this->assertSame('76561198000000004', $user->steamid);
        $this->assertSame('https://avatars.example/a.jpg', $user->avatar);
    }

    public function testBannedUserIsLoggedOutAndSentHome()
    {
        $this->actingAs($this->createUser(['banned' => true]))
            ->get('/account')
            ->assertRedirect('/');

        $this->assertGuest();
    }

    public function testUsernameSearchIsGone()
    {
        $this->get('/search/users/autocomplete?query=%25')->assertNotFound();
    }
}
