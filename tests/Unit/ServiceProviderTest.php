<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Orchestra\Testbench\TestCase;
use codeorspulse\Agent\Console\Commands\ReportCommand;
use codeorspulse\Agent\Middleware\RequestTaggingMiddleware;
use codeorspulse\Agent\codeorspulseServiceProvider;
use codeorspulse\Agent\Services\ConfigService;
use codeorspulse\Agent\Services\ReportService;

uses(TestCase::class);

beforeEach(function () {
    $this->app->register(codeorspulseServiceProvider::class);
});

it('registers config and report services as singletons', function () {
    $configA = $this->app->make(ConfigService::class);
    $configB = $this->app->make(ConfigService::class);
    $reportA = $this->app->make(ReportService::class);
    $reportB = $this->app->make(ReportService::class);

    expect($configA)->toBe($configB);
    expect($reportA)->toBe($reportB);
});

it('tags all nine collectors', function () {
    $collectors = $this->app->tagged('codeorspulse.collectors');

    expect($collectors)->toHaveCount(9);
});

it('registers the report command', function () {
    $kernel = $this->app->make(Kernel::class);
    $commands = $kernel->all();

    expect($commands)->toHaveKey('codeorspulse:report');
    expect($commands['codeorspulse:report'])->toBeInstanceOf(ReportCommand::class);
});

it('schedules the report command every minute', function () {
    $schedule = $this->app->make(Schedule::class);
    $events = $schedule->events();

    $reportEvent = collect($events)->first(fn ($event) => str_contains($event->command, 'codeorspulse:report'));

    expect($reportEvent)->not->toBeNull();
    expect($reportEvent->expression)->toBe('* * * * *');
});

it('is configured for auto-discovery', function () {
    $composerJson = json_decode(file_get_contents(__DIR__.'/../../composer.json'), true);

    $providers = $composerJson['extra']['laravel']['providers'] ?? [];
    expect($providers)->toContain(codeorspulseServiceProvider::class);
});

it('auto-registers request tagging middleware globally', function () {
    $kernel = $this->app->make(HttpKernel::class);

    $middleware = $kernel->getGlobalMiddleware();

    expect($middleware)->toContain(RequestTaggingMiddleware::class);
});
