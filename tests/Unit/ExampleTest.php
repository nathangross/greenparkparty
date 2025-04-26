<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

test('that true is true', function () {
    uses(RefreshDatabase::class);
    expect(true)->toBeTrue();
});
