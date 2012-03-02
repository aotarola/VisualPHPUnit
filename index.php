<?php

/**
 * VisualPHPUnit
 *
 * @author    Nick Sinopoli <NSinopoli@gmail.com>
 * @copyright Copyright (c) 2011-2012, Nick Sinopoli
 * @license   http://opensource.org/licenses/bsd-license.php The BSD License
 */

    require 'config.php';

    // Helper functions
    function get_snapshots() {
        $results = array();
        $handler = opendir(SNAPSHOT_DIRECTORY);
        while ( $file = readdir($handler) ) {
            if ( strpos($file, '.') !== 0 && strtolower(pathinfo($file, PATHINFO_EXTENSION)) == 'html' ) {
                $results[] = $file;
            }
        }
        closedir($handler);
        rsort($results);

        return $results;
    }

    // AJAX calls
    if ( isset($_GET['dir']) ) {
        if ( !file_exists($_GET['dir']) ) {
            if ( $_GET['type'] == 'dir' ) {
                echo 'Directory does not exist!';
            } else {
                echo 'File does not exist!';
            }
        } elseif ( !is_writable($_GET['dir']) ) {
            if ( $_GET['type'] == 'dir' ) {
                echo 'Directory is not writable! (Check permissions.)';
            } else {
                echo 'File is not writable! (Check permissions.)';
            }
        } else {
            echo 'OK';
        }
        exit;
    } elseif ( isset($_GET['snapshots']) && $_GET['snapshots'] == '1' ) {
        $results = get_snapshots();
        echo json_encode($results);
        exit;
    }

    if ( empty($_POST) ) {
        $results = get_snapshots();

        include 'ui/index.html';
        exit;
    }

    // Archives
    if ( isset($_POST['view_snapshot']) && $_POST['view_snapshot'] == 1 ) {
        $dir = realpath(SNAPSHOT_DIRECTORY) . '/';
        $snapshot = realpath($dir . $_POST['select_snapshot']);

        ob_start();
        include $snapshot;
        $content = ob_get_contents();
        ob_end_clean();
        echo $content;
        exit;
    }

    require 'lib/VPU.php';
    $vpu = new VPU();

    // Graphs
    if ( isset($_POST['graph_type']) ) {
        $graph_type = $_POST['graph_type'];
        $time_frame = $_POST['time_frame'];
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];

        require 'lib/PDO_MySQL.php';
        $config = array(
            'database' => DATABASE_NAME,
            'host'     => DATABASE_HOST,
            'port'     => DATABASE_PORT,
            'username' => DATABASE_USER,
            'password' => DATABASE_PASS
        );
        $db = new PDO_MySQL($config);

        echo $vpu->build_graph($graph_type, $time_frame, $start_date, $end_date, $db);
        exit;
    }

    // Tests
    $store_statistics = (boolean) $_POST['store_statistics'];
    $create_snapshots = (boolean) $_POST['create_snapshots'];
    $snapshot_directory = $_POST['snapshot_directory'];
    $sandbox_errors = (boolean) $_POST['sandbox_errors'];
    $sandbox_filename = $_POST['sandbox_filename'];
    if ( isset($_POST['sandbox_ignore']) && !empty($_POST['sandbox_ignore']) ) {
        $sandbox_ignore = array();
        foreach ( $_POST['sandbox_ignore'] as $ignore ) {
            $sandbox_ignore[] = $ignore;
        }
        $sandbox_ignore = implode('|', $sandbox_ignore);
    } else {
        $sandbox_ignore = '';
    }
    $test_files = $_POST['test_files'];
    $tests = explode('|', $test_files);

    ob_start();

    if ( $sandbox_errors ) {
        set_error_handler(array($vpu, 'handle_errors'));
    }

    $results = $vpu->run($tests);

    if ( $store_statistics ) {
        require 'lib/PDO_MySQL.php';
        $config = array(
            'database' => DATABASE_NAME,
            'host'     => DATABASE_HOST,
            'port'     => DATABASE_PORT,
            'username' => DATABASE_USER,
            'password' => DATABASE_PASS
        );
        $db = new PDO_MySQL($config);
        $vpu->save_results($results, $db);
    }

    ob_start();
    include 'ui/header.html';
    echo $vpu->to_HTML($results, $sandbox_errors);
    $content = ob_get_contents();
    ob_end_clean();

    echo $content;

    if ( $create_snapshots ) {
        $snapshot = ob_get_contents();
        $vpu->create_snapshot($snapshot, $snapshot_directory);
    }

?>
