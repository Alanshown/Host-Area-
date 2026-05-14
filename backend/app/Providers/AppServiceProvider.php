<?php

namespace App\Providers;

use App\Support\ProjectEnv;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        config([
            'services.siliconflow.api_url' => ProjectEnv::get('SILICONFLOW_API_URL', config('services.siliconflow.api_url')),
            'services.siliconflow.api_key' => ProjectEnv::get('SILICONFLOW_API_KEY', config('services.siliconflow.api_key')),
            'services.siliconflow.ca_bundle' => ProjectEnv::get('SILICONFLOW_CA_BUNDLE', config('services.siliconflow.ca_bundle')),
            'services.siliconflow.model' => ProjectEnv::get('SILICONFLOW_MODEL', config('services.siliconflow.model')),
            'services.siliconflow.max_tokens' => (int) ProjectEnv::get('SILICONFLOW_MAX_TOKENS', config('services.siliconflow.max_tokens')),
            'services.siliconflow.temperature' => (float) ProjectEnv::get('SILICONFLOW_TEMPERATURE', config('services.siliconflow.temperature')),
            'services.siliconflow.bot_name' => ProjectEnv::get('SILICONFLOW_BOT_NAME', config('services.siliconflow.bot_name')),
            'services.siliconflow.system_role' => ProjectEnv::get('SILICONFLOW_SYSTEM_ROLE', config('services.siliconflow.system_role')),
            'services.siliconflow.context_soft_limit' => (int) ProjectEnv::get('SILICONFLOW_CONTEXT_SOFT_LIMIT', config('services.siliconflow.context_soft_limit')),
            'services.siliconflow.summary_trigger_chars' => (int) ProjectEnv::get('SILICONFLOW_SUMMARY_TRIGGER_CHARS', config('services.siliconflow.summary_trigger_chars')),
            'services.tavily.api_url' => ProjectEnv::get('TAVILY_API_URL', config('services.tavily.api_url')),
            'services.tavily.api_key' => ProjectEnv::get('TAVILY_API_KEY', config('services.tavily.api_key')),
            'services.tavily.ca_bundle' => ProjectEnv::get('TAVILY_CA_BUNDLE', ProjectEnv::get('SILICONFLOW_CA_BUNDLE', config('services.tavily.ca_bundle'))),
            'services.tavily.max_results' => (int) ProjectEnv::get('TAVILY_MAX_RESULTS', config('services.tavily.max_results')),
        ]);
    }
}
