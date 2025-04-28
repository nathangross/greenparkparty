<?php

namespace Tests\Feature\Traits;

use Spatie\Newsletter\Facades\Newsletter;
use Illuminate\Support\Facades\Facade;

trait WithNewsletterMock
{
    protected function setUpNewsletterMock()
    {
        $mock = \Mockery::mock('Spatie\Newsletter\Newsletter');
        $mock->shouldReceive('subscribe')->andReturn(true);
        $mock->shouldReceive('getApi->post')->andReturn(true);
        
        Facade::clearResolvedInstance(Newsletter::class);
        $this->app->instance(Newsletter::class, $mock);
    }
} 