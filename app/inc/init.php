<?php

declare(strict_types=1);

use ReleaseInsights\Request;
use ReleaseInsights\Utils;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Extra\Intl\IntlExtension;
use Twig\Loader\FilesystemLoader;

// We always work with UTF8 encoding
mb_internal_encoding('UTF-8');

// Make sure we have a timezone set
date_default_timezone_set('America/Los_Angeles');

// Autoloading of classes (both /vendor/ and /app/classes)
define('INSTALL_ROOT', realpath(__DIR__ . '/../../') . '/');
require_once INSTALL_ROOT . 'vendor/autoload.php';

// Prepare caching
define('CACHE_PATH', INSTALL_ROOT . 'cache/');
define('CACHE_ENABLED', ! isset($_GET['nocache']));
define('CACHE_TIME', 3600 * 72);

// Cache Product Details versions, 12H cache
$firefox_versions = Utils::getJson(
    'https://product-details.mozilla.org/1.0/firefox_versions.json',
    43200
);

define('ESR', $firefox_versions['FIREFOX_ESR']);
define('ESR_NEXT', $firefox_versions['FIREFOX_ESR_NEXT']);
define('FIREFOX_NIGHTLY', $firefox_versions['FIREFOX_NIGHTLY']);
define('DEV_EDITION', $firefox_versions['FIREFOX_DEVEDITION']);
define('FIREFOX_BETA', $firefox_versions['LATEST_FIREFOX_RELEASED_DEVEL_VERSION']);
define('FIREFOX_RELEASE', $firefox_versions['LATEST_FIREFOX_VERSION']);

// Application globals paths
const DATA        = INSTALL_ROOT . 'app/data/';
const VIEWS       = INSTALL_ROOT . 'app/views/';
const MODELS      = INSTALL_ROOT . 'app/models/';
const CONTROLLERS = INSTALL_ROOT . 'app/controllers/';

$main_nightly = (int) FIREFOX_NIGHTLY;
$main_beta    = (int) FIREFOX_BETA;
$main_release = (int) FIREFOX_RELEASE;
$main_esr     = (int) (ESR_NEXT !== '' ? ESR_NEXT : ESR);

// Initialize our Templating system
$twig_loader = new FilesystemLoader(INSTALL_ROOT . 'app/views/templates');
$twig = new Environment($twig_loader, ['cache' => false]);
$twig->addExtension(new IntlExtension());
$twig->addExtension(new DebugExtension());
$twig->enableDebug();

// Dispatch urls
include CONTROLLERS . $url->getController() . '.php';
