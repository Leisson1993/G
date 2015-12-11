<?php
// Init
error_reporting(NULL);
ob_start();
session_start();
$TAB = 'MAIL';

include($_SERVER['DOCUMENT_ROOT']."/inc/main.php");


// Check POST request for mail domain
if (!empty($_POST['ok'])) {

    // Check token
    if ((!isset($_POST['token'])) || ($_SESSION['token'] != $_POST['token'])) {
        header('location: /login/');
        exit;
    }

    // Check empty fields
    if (empty($_POST['v_domain'])) $errors[] = __('domain');
    if (!empty($errors[0])) {
        foreach ($errors as $i => $error) {
            if ( $i == 0 ) {
                $error_msg = $error;
            } else {
                $error_msg = $error_msg.", ".$error;
            }
        }
        $_SESSION['error_msg'] = __('Field "%s" can not be blank.',$error_msg);
    }

    // Check antispam option
    if (!empty($_POST['v_antispam'])) {
        $v_antispam = 'yes';
    } else {
        $v_antispam = 'no';
    }

    // Check antivirus option
    if (!empty($_POST['v_antivirus'])) {
        $v_antivirus = 'yes';
    } else {
        $v_antivirus = 'no';
    }

    // Check dkim option
    if (!empty($_POST['v_dkim'])) {
        $v_dkim = 'yes';
    } else {
        $v_dkim = 'no';
    }

    // Set domain name to lowercase and remove www prefix
    $v_domain = preg_replace("/^www./i", "", $_POST['v_domain']);
    $v_domain = strtolower($v_domain);

    // Add mail domain
    if (empty($_SESSION['error_msg'])) {
        v_exec('v-add-mail-domain', [$user, $v_domain, $v_antispam, $v_antivirus, $v_dkim]);
    }

    // Flush field values on success
    if (empty($_SESSION['error_msg'])) {
        $_SESSION['ok_msg'] = __('MAIL_DOMAIN_CREATED_OK', htmlentities($_POST['v_domain']), htmlentities($_POST['v_domain']));
        unset($v_domain);
    }
}


// Check POST request for mail account
if (!empty($_POST['ok_acc'])) {

    // Check token
    if ((!isset($_POST['token'])) || ($_SESSION['token'] != $_POST['token'])) {
        header('location: /login/');
        exit;
    }

    // Check empty fields
    if (empty($_POST['v_domain'])) $errors[] = __('domain');
    if (empty($_POST['v_account'])) $errors[] = __('account');
    if (empty($_POST['v_password'])) $errors[] = __('password');
    if (!empty($errors[0])) {
        foreach ($errors as $i => $error) {
            if ( $i == 0 ) {
                $error_msg = $error;
            } else {
                $error_msg = $error_msg.", ".$error;
            }
        }
        $_SESSION['error_msg'] = __('Field "%s" can not be blank.', $error_msg);
    }

    // Protect input
    $v_domain = strtolower($_POST['v_domain']);
    $v_account = $_POST['v_account'];
    $v_quota = $_POST['v_quota'];
    $v_aliases = $_POST['v_aliases'];
    $v_fwd = $_POST['v_fwd'];
    if (empty($_POST['v_quota'])) $v_quota = '0';
    if ((!empty($_POST['v_quota'])) || (!empty($_POST['v_aliases'])) || (!empty($_POST['v_fwd'])) ) $v_adv = 'yes';

    // Add Mail Account
    if (empty($_SESSION['error_msg'])) {
        $v_password = tempnam("/tmp","vst");
        $fp = fopen($v_password, "w");
        fwrite($fp, $_POST['v_password']."\n");
        fclose($fp);
        v_exec('v-add-mail-account', [$user, $v_domain, $v_account, $v_password, $v_quota]);
        unlink($v_password);
        $v_password = $_POST['v_password'];
    }

    // Add Aliases
    if ((!empty($_POST['v_aliases'])) && (empty($_SESSION['error_msg']))) {
        $valiases = preg_replace('/\n/', ' ', $_POST['v_aliases']);
        $valiases = preg_replace('/,/', ' ', $valiases);
        $valiases = preg_replace('/\s+/', ' ',$valiases);
        $valiases = trim($valiases);
        $aliases = explode(' ', $valiases);
        foreach ($aliases as $alias) {
            if (empty($_SESSION['error_msg'])) {
                v_exec('v-add-mail-account-alias', [$user, $v_domain, $v_account, $alias]);
            }
        }
    }

    // Add Forwarders
    if ((!empty($_POST['v_fwd'])) && (empty($_SESSION['error_msg']))) {
        $vfwd = preg_replace('/\n/', ' ', $_POST['v_fwd']);
        $vfwd = preg_replace('/,/', ' ', $vfwd);
        $vfwd = preg_replace('/\s+/', ' ',$vfwd);
        $vfwd = trim($vfwd);
        $fwd = explode(' ', $vfwd);
        foreach ($fwd as $forward) {
            if (empty($_SESSION['error_msg'])) {
                v_exec('v-add-mail-account-forward', [$user, $v_domain, $v_account, $forward]);
            }
        }
    }

    // Add fwd_only flag
    if ((!empty($_POST['v_fwd_only'])) && (empty($_SESSION['error_msg']))) {
        v_exec('v-add-mail-account-fwd-only', [$user, $v_domain, $v_account]);
    }

    // Get webmail url
    if (empty($_SESSION['error_msg'])) {
        list($http_host, $port) = explode(':', $_SERVER['HTTP_HOST'].':');
        $webmail = "http://$http_host/webmail/";
        if (!empty($_SESSION['MAIL_URL'])) $webmail = $_SESSION['MAIL_URL'];
    }

    // Flush field values on success
    if (empty($_SESSION['error_msg'])) {
        $_SESSION['ok_msg'] = __('MAIL_ACCOUNT_CREATED_OK', htmlentities(strtolower($_POST['v_account'])), htmlentities($_POST['v_domain']), htmlentities(strtolower($_POST['v_account'])), htmlentities($_POST['v_domain']));
        $_SESSION['ok_msg'] .= " / <a href=$webmail target='_blank'>" . __('open webmail') . '</a>';
        unset($v_account);
        unset($v_password);
        unset($v_password);
        unset($v_aliases);
        unset($v_fwd);
        unset($v_quota);
    }
}

// Header
include($_SERVER['DOCUMENT_ROOT'].'/templates/header.html');

// Panel
top_panel($user,$TAB);

// Display body for mail domain
if (empty($_GET['domain']))  {
    include($_SERVER['DOCUMENT_ROOT'].'/templates/admin/add_mail.html');
}

// Display body for mail account
if (!empty($_GET['domain']))  {
    $v_domain = $_GET['domain'];
    include($_SERVER['DOCUMENT_ROOT'].'/templates/admin/add_mail_acc.html');
}

// Flush session messages
unset($_SESSION['error_msg']);
unset($_SESSION['ok_msg']);

// Footer
include($_SERVER['DOCUMENT_ROOT'].'/templates/footer.html');
