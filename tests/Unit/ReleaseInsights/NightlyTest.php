<?php

declare(strict_types=1);

use ReleaseInsights\Nightly;

test('Nightly Class', function () {
    $obj = new Nightly(TEST_FILES, TEST_FILES, 'nightly_updates_off.json');
    expect($obj->version)->toEqual('95.0a1');
    expect($obj->auto_updates)->toBeFalse();
    expect($obj->emergency_message)->toEqual('Nightly updates are disabled');
});