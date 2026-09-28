<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('earnings:recognize')->daily();
Schedule::command('payouts:sweep-stuck')->everyFiveMinutes();
