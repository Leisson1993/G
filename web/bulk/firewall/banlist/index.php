<?php
// Init
error_reporting(NULL);
ob_start();
session_start();

// Main include
include($_SERVER['DOCUMENT_ROOT']."/inc/main.php");

// Check token
if ((!isset($_POST['token'])) || ($_SESSION['token'] != $_POST['token'])) {
    header('location: /login/');
    exit;
}

// Check user
if ($_SESSION['user'] != 'admin') {
    header("Location: /list/user");
    exit;
}

$ipchain = $_POST['ipchain'];
/*if (!empty($_POST['ipchain'])) {
    $ipchain = $_POST['ipchain'];
    list($ip, $chain) = explode(':', $ipchain);
}*/

$action = $_POST['action'];

switch ($action) {
    case 'delete': $cmd='v-delete-firewall-ban';
        break;
    default: header("Location: /list/firewall/banlist/"); exit;
}

foreach ($ipchain as $value) {
    list($ip, $chain) = explode(':', $value);
    v_exec($cmd, [$ip, $chain], false);
}

header("Location: /list/firewall/banlist");
