<?php

namespace codeorspulse\Agent;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Log\LogManager;
use Illuminate\Support\ServiceProvider;
use codeorspulse\Agent\Collectors\DatabaseCollector;
use codeorspulse\Agent\Collectors\DomainCollector;
use codeorspulse\Agent\Collectors\GitCollector;
use codeorspulse\Agent\Collectors\LaravelCollector;
use codeorspulse\Agent\Collectors\LogsCollector;
use codeorspulse\Agent\Collectors\PhpCollector;
use codeorspulse\Agent\Collectors\SecurityCollector;
use codeorspulse\Agent\Collectors\ServerCollector;
use codeorspulse\Agent\Collectors\WebServerCollector;
use codeorspulse\Agent\Console\Commands\ReportCommand;
use codeorspulse\Agent\Middleware\BlockMiddleware;
use codeorspulse\Agent\Middleware\RequestTaggingMiddleware;
use codeorspulse\Agent\Monolog\codeorspulseHandler;
use codeorspulse\Agent\Services\ConfigService;
use codeorspulse\Agent\Services\ReportService;

class codeorspulseServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/codeorspulse.php', 'services.codeorspulse'
        );


        $this->app->singleton(ConfigService::class);
        $this->app->singleton(ReportService::class);
        $this->app->singleton(codeorspulseHandler::class);

        $this->app->tag(ServerCollector::class, 'codeorspulse.collectors');
        $this->app->tag(WebServerCollector::class, 'codeorspulse.collectors');
        $this->app->tag(PhpCollector::class, 'codeorspulse.collectors');
        $this->app->tag(DatabaseCollector::class, 'codeorspulse.collectors');
        $this->app->tag(GitCollector::class, 'codeorspulse.collectors');
        $this->app->tag(LogsCollector::class, 'codeorspulse.collectors');
        $this->app->tag(SecurityCollector::class, 'codeorspulse.collectors');
        $this->app->tag(LaravelCollector::class, 'codeorspulse.collectors');
        $this->app->tag(DomainCollector::class, 'codeorspulse.collectors');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/codeorspulse.php' => config_path('codeorspulse.php'),
        ], 'codeorspulse-config');

        $this->commands([ReportCommand::class]);

        $this->callAfterResolving(Kernel::class, function (Kernel $kernel): void {
            if (method_exists($kernel, 'prependMiddleware')) {
                /** @var \Illuminate\Foundation\Http\Kernel $kernel */
                $kernel->prependMiddleware(BlockMiddleware::class);
            }

            if (method_exists($kernel, 'pushMiddleware')) {
                /** @var \Illuminate\Foundation\Http\Kernel $kernel */
                $kernel->pushMiddleware(RequestTaggingMiddleware::class);
            }
        });

        $this->callAfterResolving(Schedule::class, function (Schedule $schedule): void {
            $schedule->command('codeorspulse:report')
                ->everyMinute()
                ->withoutOverlapping(55);
        });

        $this->callAfterResolving('log', function (LogManager $log): void {
            $handler = app(codeorspulseHandler::class);
            $log->driver()->getLogger()->pushHandler($handler);
        });
    }
}
