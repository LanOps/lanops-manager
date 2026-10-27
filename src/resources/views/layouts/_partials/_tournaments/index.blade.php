<div class="card">
    <div class="card-header">
        <strong>{{ $tournament->name }}</strong>
        @if ($tournament->hasEvent())
            <strong> - {{ $tournament->event->display_name }}</strong>
        @endif
        @if ($tournament->hasTeams())
            - Teams of {{ $tournament->team_size }}
        @endif
        <small> - {{ $tournament->status }}</small>
    </div>
    <div class="card-body">
        @if ($tournament->hasEvent() && !$tournament->event->getEventParticipant())
            <p class="bg-info-subtle text-info-emphasis padding">Event ticket required for this Tournament</p>
        @endif
        @if (
            ($tournament->hasEvent() && $tournament->event->getEventParticipant()) ||
            (!$tournament->hasEvent())
            )
            @if (Auth::user() && !$tournament->isUserSignedUp(Auth::user()) && $tournament->signupsOpen() && !$tournament->hasTeams())
                {{ html()->form('POST', url('/tournaments/' . $tournament->slug . '/register'))->open() }}
                    <button type="submit" class="btn btn-success btn-sm w-100">Signup</button>
                </form>
            @elseif (Auth::user() && !$tournament->isUserSignedUp(Auth::user()) && $tournament->signupsOpen() && $tournament->hasTeams())
                {{ html()->form('POST', url('/tournaments/' . $tournament->slug . '/registerTeam'))->open() }}
                    <div class="row">
                        <div class="col-12 col-md-6 mb-3 @error('team_name') @enderror">
                            {{ html()->label('Team Name', 'team_name') }}
                            <input id="team_name" type="team_name" class="form-control" name="team_name" required>
                        </div>
                        <div class="col-12 col-md-6 mb-3 @error('team_password') @enderror">
                            {{ html()->label('Team Password', 'team_password') }}
                            <input id="team_password" type="team_password" class="form-control" name="team_password">
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success btn-sm w-100">Register Team</button>
                </form>
            @elseif (Auth::user() && $tournament->isUserSignedUp(Auth::user()) && $tournament->signupsOpen())
                {{ html()->form('POST', url('/tournaments/' . $tournament->slug . '/unregister'))->open() }}
                    <button type="submit" class="btn btn-danger btn-sm w-100">Remove Signup</button>
                </form>
            @endif
        @endif
        @if ($tournament->hasTeams())
            <table width="100%" class="table table-striped table-hover" id="dataTables-example">
                <thead>
                    <tr>
                        <th>Team Name</th>
                        <th>Participants</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @if ($tournament->teams)
                        @foreach ($tournament->teams as $team)
                            <tr class="table-row odd gradeX">
                                <td width="30%">{{ $team->name }} - {{ $team->getParticipantCount() }}/{{ $tournament->team_size }}</td>
                                <td>
                                    @include('layouts._partials._tournaments.participantsList', ['participants' => $team->participants])
                                </td>
                                <td>
                                    @if (
                                        Auth::user() && !$tournament->isUserSignedUp(Auth::user()) && 
                                        $tournament->signupsOpen() && 
                                        (($tournament->hasEvent() && $tournament->event->getEventParticipant()) || !$tournament->hasEvent()))
                                        {{ html()->form('POST', url('/tournaments/' . $tournament->slug . '/register'))->open() }}
					                        {{ html()->hidden('tournament_team_id', $team->id) }}
                                            <button type="submit" class="btn btn-success btn-sm w-100">Join Team</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        @elseif ($tournament->hasParticipants())
            @include('layouts._partials._tournaments.participantsList', ['participants' => $tournament->participants])
        @endif
    </div>
</div>
