<?php

namespace Tests\Feature\Traits;

use App\Models\Party;
use Illuminate\Support\Facades\View;

trait WithViewComposer
{
    protected function setUpViewComposer(?Party $party = null)
    {
        View::composer('*', function ($view) use ($party) {
            $view->with('party', $party);
        });
    }
} 