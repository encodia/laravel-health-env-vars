<?php

it('sets the desired current environment', function () {
    $initial = currentEnvironment();
    $desired = 'staging';

    expect(mockCurrentEnvironment($desired))
        ->toEqual($desired)
        ->not->toEqual($initial)
        ->and(currentEnvironment())->toEqual($desired);
});
