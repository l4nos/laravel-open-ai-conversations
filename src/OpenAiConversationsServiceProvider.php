<?php

namespace Lanos\OpenAiConversations;

use Illuminate\Support\ServiceProvider;

/**
 * Service provider for the package.
 *
 * @package Lanos\OpenAiConversations\Providers
 */
class OpenAiConversationsServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap any package services.
     *
     * @return void
     */
    public function boot()
    {
        $this->initializeMigrations();
        $this->initializeMigrationPublishing();
        $this->setupConfig();
    }

    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/openai-conversations.php', 'open_ai_conversations'
        );
    }

    /**
     * Register the package migrations.
     *
     * @return void
     */
    protected function initializeMigrations()
    {
        if ($this->app->runningInConsole()) {
            $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
        }
    }

    /**
     * Register the package's publishable resources.
     *
     * @return void
     */
    protected function initializeMigrationPublishing()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../database/migrations' => $this->app->databasePath('migrations'),
            ], 'open-ai-conversation-migrations');
        }
    }


    /**
     * Register the package's config.
     *
     * @return void
     */
    protected function setupConfig()
    {
        $this->publishes([
            __DIR__ . '/../config/openai-conversations.php' => config_path('openai-conversations.php'),
        ]);
    }

}
