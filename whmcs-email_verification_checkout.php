<?php

if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

use WHMCS\View\Menu\Item as MenuItem;
use Illuminate\Database\Capsule\Manager as Capsule;

# Constants for configuration
# Prevent unverified accounts from placing orders (true to prevent, false to allow)
define("VERIFY_EMAIL_ORDERS", true);
# Number of days to wait before deactivating an unverified account (set to 0 to disable)
define("ACCT_DEACT_DAYS", 0);
# Number of days to wait before closing an unverified account (set to 0 to disable)
define("ACCT_CLOSE_DAYS", 0);

# Function to log activity
function logActivity($description, $clientid = null) {
    // Log activity in WHMCS
    logActivity($description, $clientid);
}

# Function to send admin notifications
function sendAdminNotification($subject, $message) {
    // Send an admin notification in WHMCS
    localAPI('SendAdminEmail', [
        'customsubject' => $subject,
        'custommessage' => $message
    ], 'admin');
}

# Function to fetch accounts for closing or deactivating
function fetchAccounts($days) {
    $dateCreated = date("Y-m-d", strtotime("now - " . intval($days) . " days"));
    return Capsule::table("tblclients")
        ->where("datecreated", "=", $dateCreated)
        ->where("email_verified", "=", 0)
        ->get();
}

# Prevent orders from unverified accounts
add_hook("ShoppingCartValidateCheckout", 1, function($vars) {
    if (VERIFY_EMAIL_ORDERS === true) {
        $client = Menu::context("client");
        if (!is_null($client) && $client) {
            if ($client->isEmailAddressVerified() == false) {
                return array("<b>Before proceeding with your order, please verify your email.</b>");
            }
        }
    }
});

# Hook to deactivate unverified accounts after x days
add_hook("DailyCronJob", 1, function($vars) {
    if (intval(ACCT_DEACT_DAYS) !== 0) {
        $accountsToDeactivate = fetchAccounts(ACCT_DEACT_DAYS);
        foreach ($accountsToDeactivate as $account) {
            Capsule::table("tblclients")->where("id", $account->id)->update(array("status" => "Inactive"));
            
            // Log the action and send an admin notification
            logActivity("Account ID: {$account->id} was deactivated due to email verification timeout.", $account->id);
            sendAdminNotification("Account Deactivated", "Account ID: {$account->id} was deactivated due to email verification timeout.");
        }
    }
});

# Hook to close unverified accounts after X days
add_hook("DailyCronJob", 1, function($vars) {
    if (intval(ACCT_CLOSE_DAYS) !== 0) {
        $accountsToClose = fetchAccounts(ACCT_CLOSE_DAYS);
        foreach ($accountsToClose as $account) {
            Capsule::table("tblclients")->where("id", $account->id)->update(array("status" => "Closed"));
            
            // Log the action and send an admin notification
            logActivity("Account ID: {$account->id} was closed due to email verification timeout.", $account->id);
            sendAdminNotification("Account Closed", "Account ID: {$account->id} was closed due to email verification timeout.");
        }
    }
});

# Hook for email verification during checkout
add_hook('ClientAreaPage', 1, function($vars) {
    $currentPage = $vars['filename'];

    if ($currentPage == 'cart') {
        $custType = filter_input(INPUT_POST, 'custtype', FILTER_SANITIZE_STRING);

        if ($custType == 'account' || $custType == 'existing') {
            if (!isEmailVerified($vars['clientsdetails']['email'])) {
                redir("a=checkout&emailverification=required");
            }
        }
    }
});

# Function to check if email is verified
function isEmailVerified($email) {
    $account = Capsule::table('tblclients')
        ->where('email', $email)
        ->where('email_verified', '1')
        ->first();

    return $account ? true : false;
}
