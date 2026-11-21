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
        $this->loadMigrationsFrom([
            database_path('migrations/ijazah'),
            database_path('migrations/update1'),
            database_path('migrations/update-sk6'),
            database_path('migrations/update-pengambilan-ijazah'),
        ]);

        try {
            \Illuminate\Support\Facades\Storage::extend('google', function ($app, $config) {
                $options = [
                    'supportsAllDrives' => true,
                ];

                if (! empty($config['teamDriveId'] ?? null)) {
                    $options['teamDriveId'] = $config['teamDriveId'];
                }

                if (! empty($config['sharedFolderId'] ?? null)) {
                    $options['sharedFolderId'] = $config['sharedFolderId'];
                }

                $client = new \Google\Client();
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                $client->refreshToken($config['refreshToken']);

                if (isset($config['accessToken'])) {
                    $client->setAccessToken($config['accessToken']);
                }

                $service = new \Google\Service\Drive($client);
                $rootFolder = !empty($config['sharedFolderId']) ? '/' : (!empty($config['folder']) ? $config['folder'] : '/');
                $adapter = new \Masbug\Flysystem\GoogleDriveAdapter(
                    $service,
                    $rootFolder,
                    $options
                );
                $driver = new \League\Flysystem\Filesystem($adapter);

                return new \Illuminate\Filesystem\FilesystemAdapter($driver, $adapter);
            });
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error($e);
        }
    }
}
