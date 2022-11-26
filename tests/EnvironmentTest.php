<?php

it('sets the desidered current environment', function () {
    $initial = currentEnvironment();
    $desired = 'staging';

    expect(mockCurrentEnvironment($desired))
        ->toEqual($desired)
        ->not->toEqual($initial)
        ->and(currentEnvironment())->toEqual($desired);
});
