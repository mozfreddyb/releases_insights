<?php

declare(strict_types=1);

[
    $display_date,
    $nightly_pairs,
    $build_crashes,
    $top_sigs,
    $bug_list,
    $previous_date,
    $requested_date,
    $next_date,
    $today,
    $known_top_crashes
] = require_once MODELS . 'nightly.php';

(new ReleaseInsights\Template(
    'nightly.html.twig',
    [
        'page_title'        => 'Nightly crashes for a day',
        'css_files'         => ['base.css'],
        'css_page_id'       => 'nightly',
        'display_date'      => $display_date,
        'nightly_pairs'     => $nightly_pairs,
        'build_crashes'     => $build_crashes,
        'top_sigs'          => $top_sigs,
        'bug_list'          => $bug_list,
        'previous_date'     => $previous_date,
        'requested_date'    => $requested_date,
        'next_date'         => $next_date,
        'today'             => $today,
        'known_top_crashes' => $known_top_crashes,
    ]
))->render();

