<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('bot:timetable-updates')
    ->everyMinute()
    ->onOneServer()
    ->runInBackground();
