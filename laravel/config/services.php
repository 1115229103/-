<?php

return [
    'fastapi' => [
        'url' => env('FASTAPI_URL', 'http://localhost:8001'),
        'internal_token' => env('FASTAPI_INTERNAL_TOKEN'),
    ],
    'ffmpeg' => [
        'path' => env('FFMPEG_PATH', 'ffmpeg'),
    ],
];
