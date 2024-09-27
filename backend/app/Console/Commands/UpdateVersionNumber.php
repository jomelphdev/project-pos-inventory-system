<?php

namespace App\Console\Commands;

use App\Events\VersionUpdated;
use App\Models\Preference;
use Illuminate\Console\Command;

class UpdateVersionNumber extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data-update:version-number';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates version on every preference.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $preferences = Preference::where('version', '!=', config('app.version'));

        if (count($preferences->get()) > 0)
        {
            $preferences->update(['version' => config('app.version')]);
        }

        VersionUpdated::dispatch();
    }
}
