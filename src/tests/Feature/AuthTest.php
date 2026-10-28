<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPassword;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Mews\Captcha\Facades\Captcha;
use SocialiteProviders\Manager\OAuth2\User as SocialiteUser;
use SocialiteProviders\Steam\OpenIDValidationException;
use Tests\TestCase;

class AuthTest extends TestCase
{
    public function testEmailLoginSucceedsAndRecordsLastLogin()
    {
        $user = $this->createUser(['password' => Hash::make('correct-horse')]);

        $this->post('/login/standard', ['email' => $user->email, 'password' => 'correct-horse'])
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login);
    }

    public function testEmailLoginRejectsAWrongPassword()
    {
        $user = $this->createUser(['password' => Hash::make('correct-horse')]);

        $this->from('/login')
            ->post('/login/standard', ['email' => $user->email, 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function testBannedUserCannotLogIn()
    {
        $user = $this->createUser(['password' => Hash::make('correct-horse'), 'banned' => true]);

        $this->from('/login')
            ->post('/login/standard', ['email' => $user->email, 'password' => 'correct-horse'])
            ->assertRedirect('/login')
            ->assertSessionHas('alert-danger', 'You have been banned!');

        $this->assertGuest();
    }

    public function testLogout()
    {
        $this->actingAs($this->createUser())->post('/logout')->assertRedirect();

        $this->assertGuest();
    }

    public function testRegistrationWithCaptchaCreatesUserAndSendsVerificationEmail()
    {
        Notification::fake();
        Captcha::shouldReceive('check')->with('ABCD')->andReturn(true);

        $this->post('/register/standard', [
            'firstname' => 'Test',
            'surname'   => 'User',
            'username'  => 'Test User',
            'email'     => 'new@example.com',
            'password1' => 'a-long-password',
            'password2' => 'a-long-password',
            'captcha'   => 'ABCD',
        ])->assertRedirect('/account');

        $user = User::where('email', 'new@example.com')->firstOrFail();
        $this->assertSame('test-user', $user->username_nice);
        $this->assertTrue(Hash::check('a-long-password', $user->password));
        $this->assertNotNull($user->referral_code);
        $this->assertAuthenticatedAs($user);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function testRegistrationRejectsAWrongCaptcha()
    {
        Captcha::shouldReceive('check')->andReturn(false);

        $this->from('/register/standard')
            ->post('/register/standard', [
                'firstname' => 'Test',
                'surname'   => 'User',
                'username'  => 'testuser',
                'email'     => 'new@example.com',
                'password1' => 'a-long-password',
                'password2' => 'a-long-password',
                'captcha'   => 'WRONG',
            ])
            ->assertRedirect('/register/standard')
            ->assertSessionHasErrors('captcha');

        $this->assertSame(0, User::where('email', 'new@example.com')->count());
    }

    public function testEmailVerificationLinkVerifiesTheUser()
    {
        $user = $this->createUser(['email_verified_at' => null]);

        $url = \URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id'   => $user->id,
            'hash' => sha1($user->getEmailForVerification()),
        ]);

        $this->actingAs($user)->get($url)->assertRedirect();

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function testPasswordResetEmailIsSent()
    {
        Notification::fake();
        $user = $this->createUser();

        $this->post('/login/forgot', ['email' => $user->email])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function testPasswordResetEmailRenders()
    {
        $user = $this->createUser();

        $mail = (new ResetPassword('a-token'))->toMail($user);

        $this->assertSame('Reset Password Notification', $mail->subject);
        $this->assertStringContainsString('/login/reset/a-token', $mail->actionUrl);
    }

    public function testPasswordCanBeResetWithAValidToken()
    {
        $user = $this->createUser(['password' => Hash::make('old-password')]);
        $token = Password::broker()->createToken($user);

        $this->post('/login/reset/password', [
            'token'                 => $token,
            'email'                 => $user->email,
            'password'              => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect('/account');

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
    }

    /**
     * Query params Steam sends back to /login/steam after the user signs in.
     */
    protected const STEAM_RETURN = '/login/steam?openid.assoc_handle=1234567890&openid.signed=signed&openid.sig=sig'
        . '&openid.return_to=https%3A%2F%2Flocalhost%2Flogin%2Fsteam';

    protected function fakeSteamProvider(): Mockery\MockInterface
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('redirectUrl')->with('https://localhost/login/steam')->andReturnSelf();
        Socialite::shouldReceive('driver')->with('steam')->andReturn($provider);
        return $provider;
    }

    protected function fakeSteamLogin(string $steamId, string $avatar = 'https://avatars.example/new.jpg'): void
    {
        $this->fakeSteamProvider()->shouldReceive('user')->andReturn((new SocialiteUser())->setRaw([
            'steamid'      => $steamId,
            'personaname'  => 'Steam Person',
            'avatarfull'   => $avatar,
            'profileurl'   => 'https://steamcommunity.com/id/person',
            'avatar'       => $avatar,
            'avatarmedium' => $avatar,
            'realname'     => 'Steam Person',
        ]));
    }

    public function testSteamLoginRedirectsToSteamOpenId()
    {
        $response = $this->get('/login/steam');

        $location = $response->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith('https://steamcommunity.com/openid/login?', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $params);
        $this->assertSame('https://localhost/login/steam', $params['openid_return_to']);
        $this->assertSame('https://localhost', $params['openid_realm']);
    }

    public function testSteamLoginValidatesTheOpenIdResponseWithSteam()
    {
        $user = $this->createUser(['steamid' => '76561198000000005']);
        config(['services.steam.client_secret' => 'test-steam-key']);
        $history = [];
        $handler = HandlerStack::create(new MockHandler([
            new Response(200, [], "ns:http://specs.openid.net/auth/2.0\nis_valid:true\n"),
            new Response(200, [], json_encode(['response' => ['players' => [[
                'steamid'    => '76561198000000005',
                'avatarfull' => 'https://avatars.example/full.jpg',
            ]]]])),
        ]));
        $handler->push(Middleware::history($history));
        // The provider is built per request, so hand it the fake HTTP handler through config
        config(['services.steam.guzzle' => ['handler' => $handler]]);

        $this->get(self::STEAM_RETURN . '&openid.claimed_id='
            . urlencode('https://steamcommunity.com/openid/id/76561198000000005'))
            ->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertSame('https://avatars.example/full.jpg', $user->fresh()->avatar);
        $this->assertSame('https://steamcommunity.com/openid/login', (string) $history[0]['request']->getUri());
        $this->assertStringContainsString('steamids=76561198000000005', (string) $history[1]['request']->getUri());
    }

    public function testSteamLoginRejectsAFailedOpenIdCheck()
    {
        $this->fakeSteamProvider()->shouldReceive('user')->andThrow(new OpenIDValidationException('nope'));

        $this->get(self::STEAM_RETURN)
            ->assertRedirect('/login')
            ->assertSessionHas('alert-danger', 'Steam login failed. Please try again.');

        $this->assertGuest();
    }

    public function testSteamLoginLogsInAnExistingUserAndUpdatesTheirAvatar()
    {
        $user = $this->createUser(['steamid' => '76561198000000001', 'avatar' => 'https://avatars.example/old.jpg']);
        $this->fakeSteamLogin('76561198000000001');

        $this->get(self::STEAM_RETURN)->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertSame('https://avatars.example/new.jpg', $user->fresh()->avatar);
    }

    public function testSteamLoginSendsANewUserToRegistration()
    {
        $this->fakeSteamLogin('76561198000000002');

        $this->get(self::STEAM_RETURN)
            ->assertRedirect('/register/steam')
            ->assertSessionHas('user.steamid', '76561198000000002');

        $this->assertGuest();
    }

    public function testSteamLoginRejectsABannedUser()
    {
        $this->createUser(['steamid' => '76561198000000003', 'banned' => true]);
        $this->fakeSteamLogin('76561198000000003');

        $this->get(self::STEAM_RETURN)->assertSessionHas('alert-danger', 'You have been banned!');

        $this->assertGuest();
    }

    public function testSteamProfileLookupSurvivesASteamTimeout()
    {
        Http::fake(['api.steampowered.com/*' => fn () => throw new \Illuminate\Http\Client\ConnectionException('timed out')]);

        $this->assertNull(\App\Libraries\Helpers::getSteamProfile('76561198000000009'));
    }

    public function testSteamRegistrationCreatesAVerifiedUser()
    {
        Http::fake(['api.steampowered.com/*' => Http::response(['response' => ['players' => [['personaname' => 'Steam Person']]]])]);
        $session = ['user' => ['steamid' => '76561198000000004', 'avatar' => 'https://avatars.example/new.jpg']];

        $this->withSession($session)->get('/register/steam')->assertOk();

        $this->withSession($session)->post('/register/steam', [
            'firstname' => 'Steam',
            'surname'   => 'Person',
            'username'  => 'steamperson',
            'steamid'   => '76561198000000004',
            'avatar'    => 'https://avatars.example/new.jpg',
        ])->assertRedirect('/account');

        $user = User::where('steamid', '76561198000000004')->firstOrFail();
        $this->assertTrue($user->hasVerifiedEmail());
        $this->assertAuthenticatedAs($user);
    }
}
