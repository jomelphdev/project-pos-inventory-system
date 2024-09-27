<?php

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

App::error(function(Exception $e) {
    Log::error($e);
});

?>