<?php

namespace App\Http\Controllers;

class RobotsController extends Controller
{
    /**
     * Serve robots.txt: crawl everything in production, nothing elsewhere.
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $lines = config('app.env') === 'production'
            ? ['User-agent: *', 'Disallow: ', 'Sitemap: ' . url('sitemap.xml')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode(PHP_EOL, $lines), 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }
}
