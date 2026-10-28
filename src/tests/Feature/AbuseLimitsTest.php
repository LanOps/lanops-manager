<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\NewsComment;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Limits on what signed-out, banned or unverified visitors can do, and on
 * how the account forms answer, from the October 2026 security review.
 */
class AbuseLimitsTest extends TestCase
{
    protected function article(): NewsArticle
    {
        return factory(NewsArticle::class)->create(['user_id' => $this->createUser()->id]);
    }

    public function testBannedUserCannotComment()
    {
        $article = $this->article();

        $this->actingAs($this->createUser(['banned' => true]))
            ->post('/news/' . $article->slug . '/comments', ['comment' => 'spam'])
            ->assertRedirect('/');

        $this->assertSame(0, NewsComment::withoutGlobalScopes()->count());
    }

    public function testUnverifiedUserCannotComment()
    {
        $article = $this->article();

        $this->actingAs($this->createUser(['email_verified_at' => null]))
            ->post('/news/' . $article->slug . '/comments', ['comment' => 'spam'])
            ->assertRedirect('/register/email/verify');

        $this->assertSame(0, NewsComment::withoutGlobalScopes()->count());
    }

    public function testCommentingIsRateLimited()
    {
        $article = $this->article();
        $user = $this->createUser();

        for ($i = 0; $i < 10; $i++) {
            $this->actingAs($user)->post('/news/' . $article->slug . '/comments', ['comment' => 'hello ' . $i]);
        }
        $this->actingAs($user)
            ->post('/news/' . $article->slug . '/comments', ['comment' => 'one too many'])
            ->assertStatus(429);

        $this->assertSame(10, NewsComment::withoutGlobalScopes()->count());
    }

    public function testForgotPasswordGivesTheSameAnswerForAnUnknownEmail()
    {
        Notification::fake();
        $user = $this->createUser();

        $known = $this->from('/login/forgot')->post('/login/forgot', ['email' => $user->email]);
        $unknown = $this->from('/login/forgot')->post('/login/forgot', ['email' => 'nobody@example.com']);
        $again = $this->from('/login/forgot')->post('/login/forgot', ['email' => $user->email]);

        foreach ([$known, $unknown, $again] as $response) {
            $response->assertRedirect('/login/forgot')
                ->assertSessionHasNoErrors()
                ->assertSessionHas('status', trans('passwords.sent'));
        }
        // The second request for the same account came too soon to send another email
        Notification::assertSentToTimes($user, \App\Notifications\ResetPassword::class, 1);
    }

    public function testForgotPasswordIsRateLimited()
    {
        Notification::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login/forgot', ['email' => 'someone' . $i . '@example.com']);
        }
        $this->post('/login/forgot', ['email' => 'another@example.com'])->assertStatus(429);
    }

    public function testBannedMessageNeedsTheRightPassword()
    {
        $user = $this->createUser(['password' => Hash::make('correct-horse'), 'banned' => true]);

        $this->from('/login')
            ->post('/login/standard', ['email' => $user->email, 'password' => 'wrong'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('email')
            ->assertSessionMissing('alert-danger');
    }

    public function testChangingPasswordSignsOutOtherSessions()
    {
        $user = $this->createUser(['password' => Hash::make('old-password')]);

        // Another device, signed in before the change
        $otherDevice = ['password_hash_web' => $user->password];

        $this->actingAs($user)->post('/account', [
            'current_password' => 'old-password',
            'password1'        => 'new-password',
            'password2'        => 'new-password',
        ])->assertSessionHas('success');
        $this->assertTrue(Hash::check('new-password', $user->fresh()->password));

        $this->app['auth']->forgetGuards();
        $this->actingAs($user->fresh())->withSession($otherDevice)->get('/account')->assertRedirect('/login');
    }

    public function testLogoutNeedsAPost()
    {
        $this->actingAs($this->createUser())->get('/logout')->assertStatus(405);
        $this->assertAuthenticated();
    }

    public function testReferralCodesCannotBeResetWithAGet()
    {
        $admin = $this->createUser(['admin' => true]);
        $code = $admin->referral_code;

        // GET now finds no route (it reads as a user id), so nothing runs
        $this->actingAs($admin)->get('/admin/users/referralcodes')->assertNotFound();

        $this->assertSame($code, $admin->fresh()->referral_code);
    }

    public function testAdminCannotGiveAUserSomeoneElsesUsername()
    {
        $admin = $this->createUser(['admin' => true]);
        $taken = $this->createUser(['username' => 'taken']);
        $user = $this->createUser(['username' => 'original']);

        $this->actingAs($admin)
            ->post('/admin/users/' . $user->id, ['username' => 'taken'])
            ->assertSessionHasErrors('username');
        $this->actingAs($admin)
            ->post('/admin/users/' . $user->id, ['username' => 'original'])
            ->assertSessionHasNoErrors();

        $this->assertSame('original', $user->fresh()->username);
    }
}
