<?php

namespace App\Console\Commands;

use App\Models\PreferenceOption;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChangePreferenceOptionsToRightBool extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'modify:preference-option-bools';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'One time use script';

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
        try
        {
            DB::beginTransaction();

            foreach (PreferenceOption::all() as $option)
            {
                $value = true;
                
                if ($option->value == 0)
                {
                    $value = false;
                }

                $option->value = $value;
                $option->save();
            }

            DB::commit();
        }
        catch (Exception $e)
        {
            DB::rollBack();
        }
    }
}
