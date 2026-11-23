<?php

return [
    'queue_connection' => env('BROADCAST_QUEUE_CONNECTION', 'database'),
    'queue_name' => env('BROADCAST_QUEUE', 'broadcast-mail'),
];
