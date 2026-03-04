<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Notifications\WebPushNotification;
use Illuminate\Console\Command;

class ServiceWorkerUpdate extends Command
{
    protected $signature = 'service-worker:update';
    protected $description = 'Update service worker dinamically with latest version and cache strategy';

    public function handle()
    {
        $swPath = __DIR__ . '/../../../public/sw.js';
        file_put_contents($swPath, $this->defaultServiceWorkerContent());
        $this->info('✅ Service worker updated successfully!');
    }

    protected function defaultServiceWorkerContent()
    {
        $content = file_get_contents(__DIR__ . '/../../../storage/app/sw.js');
        $content = str_replace('{$appVersion}', env('APP_VERSION'), $content);
        $content = str_replace('{$appName}', env('APP_NAME'), $content);
        $content = preg_replace('/^\s*\/\/.*$/m', '', $content);
        $content = preg_replace('/^\s*$/m', '', $content);
        return $content;
    }
}
