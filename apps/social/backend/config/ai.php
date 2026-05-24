<?php

return [
    'driver' => env('AI_DRIVER', 'gemini'),

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'default_text_model' => env('GEMINI_TEXT_MODEL', 'gemini-1.5-flash'),
        'default_image_model' => env('GEMINI_IMAGE_MODEL', 'imagen-3.0-generate-001'),
        'base_url' => 'https://generativelanguage.googleapis.com/v1beta',
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'default_text_model' => env('OPENAI_TEXT_MODEL', 'gpt-4o-mini'),
        'default_image_model' => env('OPENAI_IMAGE_MODEL', 'dall-e-3'),
        'base_url' => 'https://api.openai.com/v1',
    ],
];
