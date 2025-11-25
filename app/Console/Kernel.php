<?php

namespace App\Console;

class Kernel
{
    protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule): void
    {
        $schedule->job(new \App\Jobs\ParseSmartphonesJob())->dailyAt('09:00');
        $schedule->job(new \App\Jobs\ParseSmartphonesJob())->dailyAt('14:00');
        $schedule->job(new \App\Jobs\ParseSmartphonesJob())->dailyAt('19:00');
    }

}
