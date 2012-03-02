<?php

/**
 * VisualPHPUnit
 *
 * @author    Nick Sinopoli <NSinopoli@gmail.com>
 * @copyright Copyright (c) 2011-2012, Nick Sinopoli
 * @license   http://opensource.org/licenses/bsd-license.php The BSD License
 */

ini_set('display_errors', 1);


$config = array(
    'pear_path' => '/usr/share/pear'
);


// The directory where this application is installed
define('BASE_INSTALL', realpath(__DIR__));

set_include_path(get_include_path() . PATH_SEPARATOR . $config['pear_path']);

// The directory where the tests reside
define('TEST_DIRECTORY', BASE_INSTALL . '/tests/');

/*
 * Optional settings
 */

// Whether or not to create snapshots of the test results
define('CREATE_SNAPSHOTS', false);

// The directory where the test results will be stored
define('SNAPSHOT_DIRECTORY', BASE_INSTALL . '/history/');

// Whether or not to sandbox PHP errors
define('SANDBOX_ERRORS', false);

// The file to use as a temporary storage for PHP errors during PHPUnit runs
define('SANDBOX_FILENAME', BASE_INSTALL . '/errors/errors.tmp');

// Error types to ignore (separate each type with a `|`)
// e.g. 'E_STRICT|E_NOTICE'
define('SANDBOX_IGNORE', 'E_STRICT');

// Whether or not to store the statistics in a database
// (these statistics will be used to generate graphs)
define('STORE_STATISTICS', false);

// The database settings
define('DATABASE_NAME', 'vpu');
define('DATABASE_HOST', 'localhost');
define('DATABASE_PORT', '3306');
define('DATABASE_USER', 'root');
define('DATABASE_PASS', 'admin');

// Paths to any necessary bootstraps
$bootstraps = array(
    // '/path/to/tests/bootstrap.php'
);

foreach ( $bootstraps as $bootstrap ) {
    require $bootstrap;
}

?>
