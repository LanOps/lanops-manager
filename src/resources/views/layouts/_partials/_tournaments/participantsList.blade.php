<table width="100%" class="table table-striped table-hover" id="dataTables-example">
    <thead>
        <tr>
            <th>Avatar</th>
            <th>User</th>
            @if ($tournament->hasEvent())
                <th>Seat</th>
            @endif
            @if ($tournament->hasTeams())
                <th>Team</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @if ($participants)
            {{-- One Steam request for the whole list, instead of one per row --}}
            @php
                Helpers::getSteamProfiles($participants->pluck('user.steamid')->all());
            @endphp
            @foreach ($participants as $participant)
                <tr class="table-row odd gradeX">
                    <td width="10%"><x-avatar :member="$participant->user" class="img-fluid rounded" /></td>
                    <td>
                        {{ $participant->user->username }}
                        @if ($steamProfile = Helpers::getSteamProfile($participant->user->steamid))
                            <br><span class="text-muted"><small>Steam: {{ $steamProfile["name"] }}</small></span>
                        @endif
                    </td>
                    @if ($tournament->hasEvent())
                        <td>
                            @if ($seat = $participant->getSeat())
                                {{ $seat->seat }}@if ($seat->seatingPlan) - {{ $seat->seatingPlan->name }}@endif
                            @endif
                        </td>
                    @endif
                    @if ($tournament->hasTeams() && $participant->team)
                        <td>
                            {{ $participant->team->name }}
                        </td>
                    @endif
                </tr>
            @endforeach
        @endif
    </tbody>
</table>