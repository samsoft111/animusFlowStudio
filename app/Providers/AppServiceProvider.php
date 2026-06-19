<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('studio_settings')) {
                $original = config('filesystems.disks.s3');
                $secretRaw = \App\Models\StudioSetting::get('aws_secret_access_key');
                try {
                    $secret = $secretRaw ? decrypt($secretRaw) : null;
                } catch (\Throwable $e) {
                    $secret = $secretRaw;
                }

                config([
                    'filesystems.disks.s3.key'      => \App\Models\StudioSetting::get('aws_access_key_id') ?: $original['key'],
                    'filesystems.disks.s3.secret'   => $secret ?: $original['secret'],
                    'filesystems.disks.s3.region'   => \App\Models\StudioSetting::get('aws_default_region') ?: $original['region'],
                    'filesystems.disks.s3.bucket'   => \App\Models\StudioSetting::get('aws_bucket') ?: $original['bucket'],
                    'filesystems.disks.s3.endpoint' => \App\Models\StudioSetting::get('aws_endpoint') ?: $original['endpoint'],
                    'filesystems.disks.s3.url'      => \App\Models\StudioSetting::get('aws_url') ?: $original['url'],
                ]);
            }
        } catch (\Throwable $e) {
            // Ignore during setup/migrations
        }
    }
}
