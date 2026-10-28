<?php

namespace App\Services\Google;

use App\Models\Workspace;

class GoogleSyncRunner
{
    public function __construct(private GoogleConnectService $google) {}

    public function run(Workspace $workspace): string
    {
        return $this->google->runSync($workspace);
    }
}
