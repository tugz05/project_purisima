<?php

namespace App\Providers;

use App\Models\IncidentReport;
use App\Models\Transaction;
use App\Policies\IncidentReportPolicy;
use App\Policies\TransactionPolicy;
use App\Services\Sms\LogSmsGateway;
use App\Services\Sms\SemaphoreSmsGateway;
use App\Services\Sms\SmsGatewayInterface;
use App\Services\Sms\TwilioSmsGateway;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsGatewayInterface::class, function () {
            // Semaphore: preferred gateway — works in all environments including local
            if (config('services.semaphore.api_key')) {
                return new SemaphoreSmsGateway;
            }

            // Twilio: fallback, disabled in local/testing to avoid accidental charges
            if (config('services.twilio.sid') && ! app()->isLocal() && ! app()->runningUnitTests()) {
                return new TwilioSmsGateway;
            }

            // Log-only: no gateway configured or running in local/test
            return new LogSmsGateway;
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(IncidentReport::class, IncidentReportPolicy::class);

        // Force HTTPS scheme when the app URL is https, so assets are generated securely behind ngrok
        $appUrl = config('app.url');
        if (is_string($appUrl) && str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }
    }
}
