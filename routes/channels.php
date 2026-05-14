<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('poker.tables.{tableId}', function ($user = null, int $tableId = 0): bool {
    return $tableId > 0;
});
