<?php

namespace Tests\Feature;

use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\SentMessage;
use Tests\TestCase;

class MailConfigTest extends TestCase
{
    public function testMailerComesFromMailMailer()
    {
        // phpunit.xml sets MAIL_MAILER=array. The old config/mail.php used MAIL_DRIVER,
        // which Laravel 11+ ignores, so every email silently went to the log.
        $this->assertSame('array', config('mail.default'));
        $this->assertSame('smtp', config('mail.mailers.smtp.transport'));
    }

    public function testPasswordResetEmailIsActuallySentFromAppEmail()
    {
        $user = $this->createUser();

        $user->notify(new ResetPassword('a-token'));

        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);

        /** @var SentMessage $sent */
        $sent = $messages->first();
        $email = $sent->getOriginalMessage();
        $this->assertSame($user->email, $email->getTo()[0]->getAddress());
        $this->assertSame(config('mail.from.address'), $email->getFrom()[0]->getAddress());
    }

    public function testCurrencyCodeAndSymbolAreSeparateSettings()
    {
        $this->assertSame('GBP', config('app.currency'));
        $this->assertSame('£', config('app.currency_symbol'));
    }
}
