<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Invisnik\LaravelSteamAuth\SteamAuth;
use Invisnik\LaravelSteamAuth\SteamInfo;
use Mews\Captcha\Facades\Captcha;
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
        $this->actingAs($this->createUser())->get('/logout')->assertRedirect();

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

    protected function fakeSteamLogin(string $steamId, string $avatar = 'https://avatars.example/new.jpg'): void
    {
        $this->mock(SteamAuth::class, function ($mock) use ($steamId, $avatar) {
            $mock->shouldReceive('validate')->andReturn(true);
            $mock->shouldReceive('getUserInfo')->andReturn(new SteamInfo([
                'steamid'      => $steamId,
                'personaname'  => 'Steam Person',
                'avatarfull'   => $avatar,
                'profileurl'   => 'https://steamcommunity.com/id/person',
                'avatar'       => $avatar,
                'avatarmedium' => $avatar,
                'realname'     => 'Steam Person',
            ]));
        });
    }

    public function testSteamLoginRedirectsToSteamWhenNotReturningFromIt()
    {
        $this->mock(SteamAuth::class, function ($mock) {
            $mock->shouldReceive('validate')->andReturn(false);
            $mock->shouldReceive('redirect')->andReturn(redirect('https://steamcommunity.com/openid/login'));
        });

        $this->get('/login/steam')->assertRedirect('https://steamcommunity.com/openid/login');
    }

    public function testSteamLoginLogsInAnExistingUserAndUpdatesTheirAvatar()
    {
        $user = $this->createUser(['steamid' => '76561198000000001', 'avatar' => 'https://avatars.example/old.jpg']);
        $this->fakeSteamLogin('76561198000000001');

        $this->get('/login/steam')->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
        $this->assertSame('https://avatars.example/new.jpg', $user->fresh()->avatar);
    }

    public function testSteamLoginSendsANewUserToRegistration()
    {
        $this->fakeSteamLogin('76561198000000002');

        $this->get('/login/steam')
            ->assertRedirect('/register/steam')
            ->assertSessionHas('user.steamid', '76561198000000002');

        $this->assertGuest();
    }

    public function testSteamLoginRejectsABannedUser()
    {
        $this->createUser(['steamid' => '76561198000000003', 'banned' => true]);
        $this->fakeSteamLogin('76561198000000003');

        $this->get('/login/steam')->assertSessionHas('alert-danger', 'You have been banned!');

        $this->assertGuest();
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
