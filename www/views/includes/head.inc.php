<head>
    <meta charset="utf-8">

    <!-- CSS for all pages -->
    <link rel="stylesheet" type="text/css" href="/resources/styles/reset.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/normalize.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/common.css?<?= VERSION ?>">
    <link rel="stylesheet" type="text/css" href="/resources/styles/main.css?<?= VERSION ?>">

    <!-- To tell mobile browsers to adjust the width of the window to the width of the device's screen, and set the document scale to 100% of its intended size -->
    <meta name="viewport" content="width=device-width,initial-scale=1">

    <?php
    /**
     *  Load additional CSS files depending on the current URI
     */
    $additionalCss = array(
        "run"      => "run.css",
        "browse"   => "browse.css",
        "stats"    => "stats-hosts.css",
        "hosts"    => "stats-hosts.css",
        "host"     => "stats-hosts.css",
        "settings" => "settings.css",
        "cves"     => "cve.css",
        "cve"      => "cve.css"
    );

    foreach ($additionalCss as $uri => $css) {
        if (__ACTUAL_URI__[1] == $uri) {
            echo '<link rel="stylesheet" type="text/css" href="/resources/styles/' . $css . '?' . VERSION . '">';
        }
    } ?>

    <!-- Load pre JS -->
    <script src="/resources/js/pre/functions/global.js?<?= VERSION ?>"></script>
    <script src="/resources/js/pre/pre.js?<?= VERSION ?>"></script>
    <!-- jQuery -->
    <script src="/resources/js/libs/jquery-3.7.1.min.js?<?= VERSION ?>"></script>
    <!-- Select2 https://select2.org/ -->
    <script src="/resources/js/libs/select2.js?<?= VERSION ?>"></script>
    <link rel="stylesheet" type='text/css' href="/resources/styles/select2.css?<?= VERSION ?>">
    <!-- ChartJS -->
    <script src="/resources/js/libs/chartjs-4.4.8.umd.js?<?= VERSION ?>"></script>
    <!-- Favicon -->
    <link rel="icon" href="/assets/favicon.ico" />

    <?php
    $title = 'Repomanager';

    if (__ACTUAL_URI__[1] == "") {
        $title .= ' - Repos';
        echo '<script src="/resources/js/pre/functions/repo.js?' . VERSION . '"></script>';
    } elseif (__ACTUAL_URI__[1] == "run") {
        $title .= ' - Tasks';
    } elseif (__ACTUAL_URI__[1] == "browse") {
        $title .= ' - Browse repo';
    } elseif (__ACTUAL_URI__[1] == "stats") {
        $title .= ' - Statistics and metrics';
    } elseif (__ACTUAL_URI__[1] == "hosts") {
        $title .= ' - Manage hosts';
    } elseif (__ACTUAL_URI__[1] == "host") {
        $title .= ' - Manage host';
    } elseif (__ACTUAL_URI__[1] == "settings") {
        $title .= ' - Settings';
    } elseif (__ACTUAL_URI__[1] == "history") {
        $title .= ' - History';
    } elseif (__ACTUAL_URI__[1] == "userspace") {
        $title .= ' - Userspace';
    } ?>

    <title><?= $title ?></title>
</head>