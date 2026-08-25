<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Comparison preview safety switches
    |--------------------------------------------------------------------------
    |
    | The public comparison preview can use local or approved source snapshots,
    | but it must not activate financial rewards while partner settlement rules
    | and owner approvals remain incomplete.
    |
    */
    'preview_enabled' => env('COMPARISON_PREVIEW_ENABLED', true),
    'rewards_enabled' => env('COMPARISON_REWARDS_ENABLED', false),
    'vouchers_enabled' => env('COMPARISON_VOUCHERS_ENABLED', false),
    'gifts_enabled' => env('COMPARISON_GIFTS_ENABLED', false),
];
