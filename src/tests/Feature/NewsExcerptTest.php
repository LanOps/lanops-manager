<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use Tests\TestCase;

class NewsExcerptTest extends TestCase
{
    public function test_excerpt_keeps_gaps_between_blocks_and_drops_leading_title(): void
    {
        $article = new NewsArticle();
        $article->title = 'GOTY PRIZE RAFFLE';
        $article->article = '<p><u><b>GOTY PRIZE RAFFLE</b></u></p><p>We are doing something&nbsp;special.</p><p>Fish &amp; chips<br>after.</p>';

        $this->assertSame('We are doing something special. Fish & chips after.', $article->getExcerpt());
    }

    public function test_excerpt_is_cut_to_length(): void
    {
        $article = new NewsArticle();
        $article->title = 'News';
        $article->article = '<p>' . str_repeat('word ', 100) . '</p>';

        $excerpt = $article->getExcerpt();
        $this->assertStringEndsWith('...', $excerpt);
        $this->assertLessThanOrEqual(163, mb_strlen($excerpt));
    }
}
