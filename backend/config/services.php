<?php

use App\Support\ProjectEnv;

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'siliconflow' => [
        'api_url' => ProjectEnv::get('SILICONFLOW_API_URL', 'https://api.siliconflow.cn/v1/chat/completions'),
        'api_key' => ProjectEnv::get('SILICONFLOW_API_KEY'),
        'ca_bundle' => ProjectEnv::get('SILICONFLOW_CA_BUNDLE'),
        'model' => ProjectEnv::get('SILICONFLOW_MODEL', 'Pro/MiniMaxAI/MiniMax-M2.5'),
        'vision_model' => ProjectEnv::get('SILICONFLOW_VISION_MODEL', 'zai-org/GLM-4.5V'),
        'max_tokens' => (int) ProjectEnv::get('SILICONFLOW_MAX_TOKENS', 30000),
        'temperature' => (float) ProjectEnv::get('SILICONFLOW_TEMPERATURE', 0.7),
        'bot_name' => ProjectEnv::get('SILICONFLOW_BOT_NAME', 'Alma'),
        'bot_avatar' => ProjectEnv::get('SILICONFLOW_BOT_AVATAR'),
        'system_role' => ProjectEnv::get('SILICONFLOW_SYSTEM_ROLE', '你是 HostArea 社区大厅里的智能伙伴 Alma。'),
        'context_soft_limit' => (int) ProjectEnv::get('SILICONFLOW_CONTEXT_SOFT_LIMIT', 12000),
        'summary_trigger_chars' => (int) ProjectEnv::get('SILICONFLOW_SUMMARY_TRIGGER_CHARS', 6000),
    ],

    'tavily' => [
        'api_url' => ProjectEnv::get('TAVILY_API_URL', 'https://api.tavily.com/search'),
        'api_key' => ProjectEnv::get('TAVILY_API_KEY'),
        'ca_bundle' => ProjectEnv::get('TAVILY_CA_BUNDLE', ProjectEnv::get('SILICONFLOW_CA_BUNDLE')),
        'max_results' => (int) ProjectEnv::get('TAVILY_MAX_RESULTS', 7),
    ],

];
