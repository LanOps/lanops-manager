<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use Tests\TestCase;

class NewsExcerptTest extends TestCase
{
    public function testExcerptKeepsGapsBetweenBlocksAndDropsLeadingTitle(): void
    {
        $article = new NewsArticle();
        $article->title = 'GOTY PRIZE RAFFLE';
        $article->article = '<p><u><b>GOTY PRIZE RAFFLE</b></u></p><p>We are doing something&nbsp;special.</p><p>Fish &amp; chips<br>after.</p>';

        $this->assertSame('We are doing something special. Fish & chips after.', $article->getExcerpt());
    }

    public function testExcerptIsCutToLength(): void
    {
        $article = new NewsArticle();
        $article->title = 'News';
        $article->article = '<p>' . str_repeat('word ', 100) . '</p>';

        $excerpt = $article->getExcerpt();
        $this->assertStringEndsWith('...', $excerpt);
        $this->assertLessThanOrEqual(163, mb_strlen($excerpt));
    }
}
