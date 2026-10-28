<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Links in emails (password resets, email verification) must point at the
 * site, whatever Host or X-Forwarded-Host the request that sent them carried.
 */
class SiteUrlTest extends TestCase
{
    public function testLinksUseTheSiteUrlInProductionWhateverTheRequestHost()
    {
        config(['app.url' => 'https://www.lanops.co.uk']);
        $this->app['env'] = 'production';
        $this->app['url']->setRequest(Request::create('http://evil.example/login/forgot'));

        (new AppServiceProvider($this->app))->boot();

        $this->assertSame('https://www.lanops.co.uk/login/reset/abc', url('/login/reset/abc'));
        $this->assertStringStartsWith('https://www.lanops.co.uk/', route('password.reset', ['token' => 'abc']));
    }
}
