<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
        'maps_api_key' => env('GOOGLE_MAPS_API_KEY'),
    ],

    'gmail_api' => [
        'client_id' => env('GOOGLE_GMAIL_CLIENT_ID'),
        'client_secret' => env('GOOGLE_GMAIL_CLIENT_SECRET'),
        'refresh_token' => env('GOOGLE_GMAIL_REFRESH_TOKEN'),
        'from' => env('GOOGLE_GMAIL_FROM'),
        'token_endpoint' => env('GOOGLE_GMAIL_TOKEN_ENDPOINT', 'https://oauth2.googleapis.com/token'),
        'send_endpoint' => env('GOOGLE_GMAIL_SEND_ENDPOINT', 'https://gmail.googleapis.com/gmail/v1/users/me/messages/send'),
        'timeout' => env('GOOGLE_GMAIL_TIMEOUT', 10),
    ],

    // The crawler uses Groq's OpenAI-compatible chat-completions API.
    // Prioritizes CHATBOX_ specific keys for your separate presentation key.
    'groq' => [
        'key' => env('CHATBOX_GROQ_API_KEY', env('GROQ_API_KEY', env('OPENAI_API_KEY'))),
        'api_key' => env('CHATBOX_GROQ_API_KEY', env('GROQ_API_KEY', env('OPENAI_API_KEY'))),
        'model' => env('CHATBOX_GROQ_MODEL', env('GROQ_MODEL', env('OPENAI_MODEL', 'openai/gpt-oss-20b'))),
        'enhancement_enabled' => env('AI_CRAWLER_ENHANCEMENT_ENABLED', true),
        'endpoint' => env('GROQ_API_ENDPOINT', 'https://api.groq.com/openai/v1/chat/completions' ),
    ],

    'tavily' => [
        'api_key' => env('TAVILY_API_KEY'),
        'endpoint' => env('TAVILY_API_ENDPOINT', 'https://api.tavily.com/search' ),
        'research_enabled' => env('AI_CRAWLER_WEB_RESEARCH_ENABLED', true),
    ],

    'chatbox_groq' => [
        'key' => env('CHATBOX_GROQ_API_KEY'),
        'model' => env('CHATBOX_GROQ_MODEL', 'openai/gpt-oss-20b'),
        'endpoint' => env(
            'CHATBOX_GROQ_API_ENDPOINT',
            'https://api.groq.com/openai/v1/chat/completions'
    ),
    ],
    
];
