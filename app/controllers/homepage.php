<?php

declare(strict_types=1);

[
    $beta_cycle_dates,
    $nightly_cycle_dates,
    $today_is_release_day,
    $is_rc_week,
    $rc_build,
    $latest_nightly,
    $firefox_version_on_release_day,
    $beta_is_the_next_ESR,
] = require_once MODELS . 'home.php';

(new ReleaseInsights\Template(
    'overview.html.twig',
    [
        'page_title'             => 'What Firefox trains are we in?',
        'css_page_id'            => 'homepage',
        'beta_cycle_dates'       => $beta_cycle_dates,
        'nightly_cycle_dates'    => $nightly_cycle_dates,
        'release_day'            => $today_is_release_day,
        'rc_week'                => $is_rc_week,
        'rc_build'               => $rc_build,
        'latest_nightly'         => $latest_nightly,
        'version_on_release_day' => $firefox_version_on_release_day,
        'beta_is_the_next_ESR'   => $beta_is_the_next_ESR,
    ]
))->render();
