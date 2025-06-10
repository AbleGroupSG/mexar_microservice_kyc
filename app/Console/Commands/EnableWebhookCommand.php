<?php

namespace App\Console\Commands;

use App\Services\KYC\Regtank\RegtankAuth;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class EnableWebhookCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'enable.webhook.command {--isEnabled}';
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isEnabled = $this->option('isEnabled');

        $accessToken = RegtankAuth::getToken();
        $url = config('e-form.regtank.specific_server_url');
        $data = [
            'webhookUrl' => url('/'),
            'webhookEnabled' => $isEnabled,
        ];

        $res=  Http::withToken($accessToken)
            ->post("$url/alert/preferences", $data)
            ->json();

        dump($res);
    }
}
