<?php

namespace Tests\Feature;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollOptionVote;
use Tests\TestCase;

/**
 * Ending polls: admins can end now or set an end date, and ended polls take no more votes or options.
 */
class PollEndTest extends TestCase
{
    protected function createPoll(array $attributes = []): Poll
    {
        $poll = new Poll();
        $poll->forceFill(array_merge([
            'name'               => 'Poll ' . uniqid(),
            'status'             => 'PUBLISHED',
            'allow_options_user' => 1,
            'user_id'            => $this->createUser()->id,
        ], $attributes))->save();

        return $poll->fresh();
    }

    protected function createOption(Poll $poll): PollOption
    {
        $option = new PollOption();
        $option->forceFill(['name' => 'Option ' . uniqid(), 'poll_id' => $poll->id, 'user_id' => $poll->user_id])->save();

        return $option;
    }

    public function testAdminCanCreatePollWithEndDate()
    {
        $this->actingAs($this->createUser(['admin' => 1]))->post('/admin/polls', [
            'name'    => 'Games for LAN',
            'end'     => '2026-11-15T18:30',
            'options' => ['Rust'],
        ])->assertSessionHasNoErrors();

        $this->assertSame('2026-11-15 18:30:00', Poll::where('name', 'Games for LAN')->firstOrFail()->end);
    }

    public function testAdminCanSetAndClearEndDate()
    {
        $admin = $this->createUser(['admin' => 1]);
        $poll = $this->createPoll();

        $this->actingAs($admin)->post('/admin/polls/' . $poll->slug, [
            'name'   => $poll->name,
            'status' => 'published',
            'end'    => '2020-05-01T12:00',
        ])->assertSessionHasNoErrors();
        $this->assertTrue($poll->fresh()->hasEnded());

        $this->actingAs($admin)->get('/admin/polls/' . $poll->slug)
            ->assertOk()
            ->assertSee('value="2020-05-01T12:00"', false);

        $this->actingAs($admin)->post('/admin/polls/' . $poll->slug, [
            'name'   => $poll->name,
            'status' => 'published',
            'end'    => '',
        ])->assertSessionHasNoErrors();
        $this->assertNull($poll->fresh()->end);
    }

    public function testAdminCanEndPollNow()
    {
        $admin = $this->createUser(['admin' => 1]);
        $poll = $this->createPoll();

        $this->actingAs($admin)->get('/admin/polls/' . $poll->slug)->assertSee('End Poll Now');
        $this->actingAs($admin)->post('/admin/polls/' . $poll->slug . '/end')->assertSessionHas('alert-success');

        $this->assertTrue($poll->fresh()->hasEnded());
        $this->actingAs($admin)->get('/admin/polls/' . $poll->slug)->assertDontSee('End Poll Now');
    }

    public function testEndedPollRefusesVotesAndOptions()
    {
        $poll = $this->createPoll(['end' => now()->subDay()]);
        $option = $this->createOption($poll);
        $user = $this->createUser();

        $this->actingAs($user)->post('/polls/' . $poll->slug . '/options/' . $option->id . '/vote')
            ->assertSessionHas('alert-danger', 'Poll has ended');
        $this->actingAs($user)->post('/polls/' . $poll->slug . '/options', ['options' => ['Late']])
            ->assertSessionHas('alert-danger', 'Poll has ended');

        $this->assertSame(0, PollOptionVote::count());
        $this->assertSame(1, $poll->options()->count());
    }

    public function testEndedPollPageHasNoVoteOrAddOptionForms()
    {
        $poll = $this->createPoll(['end' => now()->subDay()]);
        $this->createOption($poll);

        $this->actingAs($this->createUser())->get('/polls/' . $poll->slug)
            ->assertOk()
            ->assertSee('Voting ended')
            ->assertSee("You didn't vote in this poll.", false)
            ->assertDontSee('/vote"', false)
            ->assertDontSee('Add Options');
    }

    public function testPollWithFutureEndDateIsStillOpen()
    {
        $poll = $this->createPoll(['end' => now()->addDay()]);
        $option = $this->createOption($poll);

        $this->actingAs($this->createUser())->get('/polls/' . $poll->slug)
            ->assertOk()
            ->assertSee('Voting closes')
            ->assertSee('Nothing yet.')
            ->assertSee('/vote"', false);

        $this->actingAs($this->createUser())->post('/polls/' . $poll->slug . '/options/' . $option->id . '/vote')
            ->assertSessionHas('alert-success');
    }

    public function testPollsIndexSplitsOpenAndEnded()
    {
        $open = $this->createPoll(['name' => 'Open Poll']);
        $scheduled = $this->createPoll(['name' => 'Scheduled Poll', 'end' => now()->addDay()]);
        $ended = $this->createPoll(['name' => 'Ended Poll', 'end' => now()->subDay()]);

        $this->assertEqualsCanonicalizing([$open->id, $scheduled->id], Poll::open()->pluck('id')->all());
        $this->assertSame([$ended->id], Poll::ended()->pluck('id')->all());

        $this->get('/polls')->assertOk()->assertSeeInOrder(['Open Poll', 'Previous Polls', 'Ended Poll']);
    }

    public function testAdminListShowsOpenPollsFirstThenNewest()
    {
        $this->createPoll(['name' => 'Old Ended Poll', 'end' => now()->subMonth(), 'created_at' => now()->subYear()]);
        $this->createPoll(['name' => 'New Ended Poll', 'end' => now()->subDay(), 'created_at' => now()->subWeek()]);
        $this->createPoll(['name' => 'Old Open Poll', 'created_at' => now()->subYears(2)]);

        $this->actingAs($this->createUser(['admin' => 1]))->get('/admin/polls')
            ->assertOk()
            ->assertSeeInOrder(['Old Open Poll', 'New Ended Poll', 'Old Ended Poll']);
    }
}
