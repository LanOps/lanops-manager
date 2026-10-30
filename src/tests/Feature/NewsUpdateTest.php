<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use Tests\TestCase;

/**
 * Editing a news article in the admin saves the article, not just its tags.
 */
class NewsUpdateTest extends TestCase
{
    public function testAdminCanUpdateArticleText()
    {
        $admin = $this->createUser(['admin' => 1]);
        $article = NewsArticle::forceCreate([
            'title' => 'Original title',
            'article' => '<p>Original text</p>',
            'user_id' => $admin->id,
        ]);
        $article->storeTags(['lan']);
        $article->refresh();

        $this->actingAs($admin)->post('/admin/news/' . $article->slug, [
            'title' => 'Original title',
            'article' => '<p>Original text</p><p>Some added text</p>',
            'tags' => 'lan, news',
        ])->assertSessionHas('alert-success', 'Successfully Updated News Article!');

        $article->refresh();
        $this->assertSame('<p>Original text</p><p>Some added text</p>', $article->article);
        $this->assertEqualsCanonicalizing(['lan', 'news'], $article->tags->pluck('tag')->all());
    }
}
