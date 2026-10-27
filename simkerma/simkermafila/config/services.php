<?php

return [

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key'    => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel'              => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
    ],

    'pdf_text_extractor' => [
        'binary' => env('PDFTOTEXT_BINARY', 'pdftotext'),
    ],

    'pdf_ocr' => [
        'renderer_binary'  => env('PDF_RENDERER_BINARY', 'pdftoppm'),
        'pdfinfo_binary'   => env('PDFINFO_BINARY', 'pdfinfo'),          // <-- tambahan
        'tesseract_binary' => env('TESSERACT_BINARY', 'tesseract'),
        'languages'        => env('TESSERACT_LANGUAGES', 'ind+eng'),
        'dpi'              => (int) env('PDF_RENDERER_DPI', env('TESSERACT_DPI', 200)),
    ],

    'web_api' => [
        'key' => env('WEB_API_KEY'),
    ],

    'polinema' => [
        'base_url' => env('POLINEMA_API_BASE_URL', 'http://api.polinema.ac.id/bidang_iv'),
        'username' => env('POLINEMA_API_USERNAME', ''),
        'password' => env('POLINEMA_API_PASSWORD', ''),
        'enabled'  => env('POLINEMA_SSO_ENABLED', true),
    ],

];