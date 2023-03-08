<?php

declare(strict_types=1);

use BzKarma\Scoring;
use ReleaseInsights\Bugzilla as Bz;
use ReleaseInsights\Utils;

/*
    We need previous and next days for navigation and changelog
    The requester date is already in the $date variable
*/
$today          = date('Ymd');
$requested_date = Utils::getDate();
$previous_date  = date('Ymd', strtotime($requested_date . ' -1 day'));
$next_date      = date('Ymd', strtotime($requested_date . ' +1 day'));

// Get nightlies for the GET Request (or today's nightly)
$nightlies = include MODELS . 'api/nightly.php';

// Store a value for the View title
$display_date = strtotime($requested_date);
$fallback_nightly = false;

// This is a fallback mechanism for Buildhub which sometimes takes hours to have the latest nightly
if (empty($nightlies)) {
    // Get the latest nightly build ID, used as a tooltip on the nightly version number
    $latest_nightly = Utils::getJson(
        'https://archive.mozilla.org/pub/firefox/nightly/latest-mozilla-central/firefox-' . FIREFOX_NIGHTLY . '.en-US.win64.json',
        3600
    );

    // We want to make sure that the latest nightly from archive.mozilla.org is not yesterday's nightly
    if (isset($latest_nightly['buildid']) && $today === date('Ymd', strtotime($latest_nightly['buildid']))) {
        $nightlies = [
            $latest_nightly['buildid'] => [
                'revision' => $latest_nightly['moz_source_stamp'],
                'version'  => FIREFOX_NIGHTLY
            ]
        ];

        $fallback_nightly = true;
    }
    unset($latest_nightly);
}

// We now fetch the previous day nightlies because we need them for changelogs
$_GET['date'] = $previous_date;
$nightlies_day_before = include MODELS . 'api/nightly.php';

// Associate nightly with nightly-1
$nightly_pairs = [];

$i = true;
$previous_changeset = null;
foreach ($nightlies as $buildid => $changeset) {
    // The first build of the day is to associate with yesterday's last build
    if ($i === true) {
        $nightly_pairs[] = [
            'buildid'        => $buildid,
            'changeset'      => $changeset['revision'],
            'version'        => $changeset['version'],
            'prev_changeset' => end($nightlies_day_before)['revision'],
        ];
        $i = false;
        $previous_changeset = $changeset['revision'];
        continue;
    }

    $nightly_pairs[] = [
        'buildid'        => $buildid,
        'changeset'      => $changeset['revision'],
        'version'        => $changeset['version'],
        'prev_changeset' => $previous_changeset,
    ];
    $previous_changeset = $changeset['revision'];
}

$build_crashes = [];
$top_sigs = [];

// We fetch crashes from Socorro for the last 10 days only
$days_elapsed = date_diff(date_create(date($today)), date_create($requested_date))->days;
if ($days_elapsed < 10) {
    foreach ($nightly_pairs as $dataset) {
        $build_crashes[$dataset['buildid']] = Utils::getCrashesForBuildID($dataset['buildid'])['total'];
    }

    foreach ($nightly_pairs as $dataset) {
        $top_sigs[$dataset['buildid']] = array_splice(
            Utils::getCrashesForBuildID($dataset['buildid'])['facets']['signature'],
            0,
            20
        );
    }
}

$bug_list = [];
$bug_list_karma = [];
$bug_list_karma_details = [];

foreach ($nightly_pairs as $dataset) {
    $bugs = Bz::getBugsFromHgWeb(
        'https://hg.mozilla.org/mozilla-central/json-pushes?fromchange=' . $dataset['prev_changeset'] . '&tochange=' . $dataset['changeset'] . '&full&version=2'
    )['total'];

    $bug_list_karma = array_unique(array_merge($bugs, $bug_list_karma));

    // There were no bugs in the build, it is the same as the previous one
    if (empty($bugs)) {
        $bug_list[$dataset['buildid']] = [
            'bugs'  => null,
            'url'   => '',
            'count' => 0,
        ];
        continue;
    }

    $url = Bz::getBugListLink($bugs);

    // Bugzilla REST API https://wiki.mozilla.org/Bugzilla:REST_API
    $bug_list_details = Utils::getJson('https://bugzilla.mozilla.org/rest/bug?include_fields=id,summary,priority,severity,keywords,product,component,type,duplicates,regressions,cf_webcompat_priority,cf_tracking_firefox' . NIGHTLY . ',cf_tracking_firefox' . BETA . ',cf_tracking_firefox' . RELEASE . ',cf_status_firefox' . NIGHTLY . ',cf_status_firefox' . BETA . ',cf_status_firefox' . RELEASE . ',cc&bug_id=' . implode('%2C', $bugs))['bugs'];

    $bug_list[$dataset['buildid']] = [
        'bugs'  => $bug_list_details,
        'url'   => $url,
        'count' => is_countable($bugs) ? count($bugs) : 0,
    ];

    $bug_list_karma_details = array_merge($bug_list_details, $bug_list_karma_details);
}


// Create the real bug list Karma
sort($bug_list_karma);
$bug_list_karma = array_map('intval',$bug_list_karma);
$bug_list_karma = array_values($bug_list_karma);
$bug_list_karma = array_flip($bug_list_karma);

// Prepare the list for use by the Scoring object
$bug_list_karma_details = array_combine(array_column($bug_list_karma_details, 'id'), $bug_list_karma_details);

$scores = new Scoring($bug_list_karma_details, RELEASE);

//  The $bug_list_karma array has bug numbers as keys and score (ints) as values
foreach ($bug_list_karma as $key => $value) {
    $bug_list_karma[$key] = [
        'score'   => $scores->getBugScore($key),
        'details' => $scores->getBugScoreDetails($key),
    ];
}

$known_top_crashes = [
    'IPCError-browser | ShutDownKill | mozilla::ipc::MessagePump::Run',
    'IPCError-browser | ShutDownKill | NtYieldExecution',
    'IPCError-browser | ShutDownKill | EMPTY: no crashing thread identified; ERROR_NO_MINIDUMP_HEADER',
    'IPCError-browser | ShutDownKill',
    'OOM | small',
];

return [
    $display_date,
    $nightly_pairs,
    $build_crashes,
    $top_sigs,
    $bug_list,
    $bug_list_karma,
    $previous_date,
    $requested_date,
    $next_date,
    $today,
    $known_top_crashes,
    $fallback_nightly
];