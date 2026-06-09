<?php

use App\Mcp\Servers\FairmdLipidsServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/fairmd-lipids', FairmdLipidsServer::class)
    ->middleware(['throttle:mcp']);
