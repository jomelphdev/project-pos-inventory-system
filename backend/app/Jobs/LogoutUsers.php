<?php

namespace App\Jobs;

use App\Events\LogoutUser;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LogoutUsers implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $userIds;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($userIds=[])
    {
        if (!is_array($userIds)) 
        {
            $userIds = [$userIds];
        }

        if (!$userIds)
        {
            $userIds = User::select('id')->get()->pluck('id');
        }

        $this->userIds = $userIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        foreach ($this->userIds as $id)
        {
            LogoutUser::dispatch($id);
        }

        return;
    }
}
