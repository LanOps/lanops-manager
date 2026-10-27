<?php

namespace Tests\Feature;

use App\Models\NewsArticle;
use App\Models\NewsComment;
use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollOptionVote;
use App\Models\Tournament;
use App\Models\TournamentParticipant;
use App\Models\TournamentTeam;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Polls, tournament teams, and actions that change data needing a POST.
 */
class PollsTournamentsAndActionsTest extends TestCase
{
    protected function createPoll(array $attributes = []): Poll
    {
        $poll = new Poll();
        $poll->forceFill(array_merge([
            'name'    => 'Poll ' . uniqid(),
            'status'  => 'PUBLISHED',
            'user_id' => $this->createUser()->id,
        ], $attributes))->save();

        return $poll->fresh();
    }

    protected function createOption(Poll $poll): PollOption
    {
        $option = new PollOption();
        $option->forceFill(['name' => 'Option ' . uniqid(), 'poll_id' => $poll->id, 'user_id' => $poll->user_id])->save();

        return $option;
    }

    protected function createTeamTournament(array $teamAttributes = []): TournamentTeam
    {
        $tournament = new Tournament();
        $tournament->name = 'CS2 ' . uniqid();
        $tournament->team_size = 2;
        $tournament->save();
        $tournament->status = Tournament::$statusOpen;
        $tournament->save();

        $team = new TournamentTeam();
        $team->forceFill(array_merge(['name' => 'Team A', 'tournament_id' => $tournament->id], $teamAttributes))->save();

        return $team->fresh();
    }

    protected function joinTeam(User $user, TournamentTeam $team, array $data = [])
    {
        return $this->actingAs($user)
            ->from('/tournaments/' . $team->tournament->slug)
            ->post('/tournaments/' . $team->tournament->slug . '/register', array_merge(['tournament_team_id' => $team->id], $data));
    }

    public function testVotingIsAPost()
    {
        $poll = $this->createPoll();
        $option = $this->createOption($poll);
        $user = $this->createUser();

        $this->actingAs($user)->get('/polls/' . $poll->slug . '/options/' . $option->id . '/vote')->assertMethodNotAllowed();
        $this->actingAs($user)->post('/polls/' . $poll->slug . '/options/' . $option->id . '/vote')->assertSessionHas('alert-success');

        $this->assertSame(1, PollOptionVote::count());
    }

    public function testCannotVoteOnAnotherPollsOptionThroughAnOpenPoll()
    {
        $open = $this->createPoll();
        $ended = $this->createPoll(['end' => now()->subDay()]);
        $endedOption = $this->createOption($ended);

        $this->actingAs($this->createUser())
            ->post('/polls/' . $open->slug . '/options/' . $endedOption->id . '/vote')
            ->assertNotFound();

        $this->assertSame(0, PollOptionVote::count());
    }

    public function testPollPageShowsVoteForms()
    {
        $poll = $this->createPoll();
        $this->createOption($poll);

        $this->actingAs($this->createUser())
            ->get('/polls/' . $poll->slug)
            ->assertOk()
            ->assertSee('/vote" class="d-inline"', false);
    }

    public function testReportingAndDeletingCommentsArePosts()
    {
        $author = $this->createUser();
        $article = factory(NewsArticle::class)->create();
        $comment = NewsComment::create(['comment' => 'Hi', 'news_feed_id' => $article->id, 'user_id' => $author->id]);
        $comment->forceFill(['approved' => 1, 'reviewed' => 1])->save();
        $base = '/news/' . $article->slug . '/comments/' . $comment->id;

        $this->actingAs($this->createUser())->get($base . '/report')->assertMethodNotAllowed();
        $this->actingAs($author)->get($base . '/delete')->assertMethodNotAllowed();

        $this->actingAs($author)->post($base . '/delete')->assertSessionHas('alert-success', 'Comment Deleted!');
        $this->assertNull(NewsComment::find($comment->id));
    }

    public function testAuthorCanDeleteTheirCommentWhileItAwaitsApproval()
    {
        $author = $this->createUser();
        $article = factory(NewsArticle::class)->create();
        $comment = NewsComment::create(['comment' => 'Hi', 'news_feed_id' => $article->id, 'user_id' => $author->id]);
        $base = '/news/' . $article->slug . '/comments/' . $comment->id;

        $this->actingAs($this->createUser())->post($base . '/delete')->assertNotFound();
        $this->assertNotNull(NewsComment::withoutGlobalScopes()->find($comment->id));

        $this->actingAs($author)
            ->get('/news/' . $article->slug)
            ->assertSee("This comment hasn't been reviewed by Admins yet.", false);
        $this->actingAs($author)->post($base . '/delete')->assertSessionHas('alert-success', 'Comment Deleted!');
        $this->assertNull(NewsComment::withoutGlobalScopes()->find($comment->id));
    }

    public function testCommentWithQuotesCannotBreakOutOfTheEditLink()
    {
        $author = $this->createUser();
        $article = factory(NewsArticle::class)->create();
        $comment = NewsComment::create([
            'comment'      => "');alert(1);//",
            'news_feed_id' => $article->id,
            'user_id'      => $author->id,
        ]);
        $comment->forceFill(['approved' => 1, 'reviewed' => 1])->save();

        $this->actingAs($author)
            ->get('/news/' . $article->slug)
            ->assertOk()
            ->assertDontSee("editComment('');alert(1);//'", false);
    }

    public function testJoiningATeamNeedsItsPassword()
    {
        $team = $this->createTeamTournament(['password' => Hash::make('secret')]);
        $user = $this->createUser();

        $this->joinTeam($user, $team)->assertSessionHas('alert-danger', 'Team password is incorrect.');
        $this->joinTeam($user, $team, ['team_password' => 'wrong'])->assertSessionHas('alert-danger', 'Team password is incorrect.');
        $this->joinTeam($user, $team, ['team_password' => 'secret'])->assertSessionHas('alert-success');

        $this->assertSame($team->id, TournamentParticipant::where('user_id', $user->id)->value('tournament_team_id'));
    }

    public function testOldPlainTextTeamPasswordsStillWork()
    {
        $team = $this->createTeamTournament(['password' => 'plain']);

        $this->joinTeam($this->createUser(), $team, ['team_password' => 'plain'])->assertSessionHas('alert-success');
    }

    public function testCannotJoinATeamFromAnotherTournament()
    {
        $team = $this->createTeamTournament();
        $other = $this->createTeamTournament();

        $this->actingAs($this->createUser())
            ->post('/tournaments/' . $other->tournament->slug . '/register', ['tournament_team_id' => $team->id])
            ->assertSessionHas('alert-danger', 'Tournament Team does not exist');

        $this->assertSame(0, TournamentParticipant::count());
    }

    public function testCannotJoinAFullTeam()
    {
        $team = $this->createTeamTournament();
        $this->joinTeam($this->createUser(), $team);
        $this->joinTeam($this->createUser(), $team);

        $this->joinTeam($this->createUser(), $team)->assertSessionHas('alert-danger', 'That team is full.');

        $this->assertSame(2, TournamentParticipant::count());
    }

    public function testNewTeamPasswordsAreHashed()
    {
        $team = $this->createTeamTournament();
        $tournament = $team->tournament;

        $this->actingAs($this->createUser())
            ->post('/tournaments/' . $tournament->slug . '/registerTeam', ['team_name' => 'Team B', 'team_password' => 'secret']);

        $created = TournamentTeam::where('name', 'Team B')->firstOrFail();
        $this->assertNotSame('secret', $created->password);
        $this->assertTrue($created->checkPassword('secret'));
    }

    public function testGalleryUploadRejectsNonImages()
    {
        $admin = $this->createUser(['admin' => true]);
        $album = new \App\Models\GalleryAlbum();
        $album->forceFill(['name' => 'Album ' . uniqid(), 'status' => 'PUBLISHED'])->save();

        $this->actingAs($admin)
            ->post('/admin/gallery/' . $album->fresh()->slug . '/upload', [
                'images' => [UploadedFile::fake()->create('shell.php', 1, 'application/x-php')],
            ])
            ->assertSessionHasErrors('images.0');

        $this->assertSame(0, $album->getMedia('images')->count());
    }

    public function testDisabledPaymentGatewayIsRefused()
    {
        $ticket = $this->createEventWithTicket();

        $this->actingAs($this->createUser())
            ->withSession($this->basketFor($ticket))
            ->from('/payment/checkout')
            ->get('/payment/review/paypal_express')
            ->assertRedirect('/payment/checkout');
    }
}
