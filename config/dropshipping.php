<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Dropshipping Admin UI V2 Feature Flag
    |--------------------------------------------------------------------------
    |
    | Controls whether the new custom Dropshipping Admin UI V2 layouts and routes 
    | are active. If set to false, navigation menus and routes point back to the
    | legacy/placeholder views.
    |
    */
    'admin_v2' => (bool) env('FEATURE_DROPSHIPPING_UI_V2', true),
];
