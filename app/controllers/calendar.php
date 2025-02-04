<?php

declare(strict_types=1);

$data = require_once MODELS . 'calendar.php';

(new ReleaseInsights\Template(
    'calendar.html.twig',
    [
        'page_title'        => 'Firefox Release Calendar',
        'css_page_id'       => 'calendar_main',
        'past_releases'     => $data['past'],
        'upcoming_releases' => $data['future'],
        'upcoming_quarters' => array_count_values(array_column($data['future'], 'quarter')),
    ]
))->render();
