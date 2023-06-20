<?php

// This checks if WHMCS is defined and if not, stops the script.
if (!defined("WHMCS"))
    die("This file cannot be accessed directly");

// Import necessary WHMCS classes and Laravel's Capsule Manager
use WHMCS\View\Menu\Item as MenuItem;
use Illuminate\Database\Capsule\Manager as Capsule;

// Define configuration constants
define("VERIFY_EMAIL_ORDERS", true);  // Boolean to determine whether to verify email before order
define("ACCT_DEACT_DAYS", 0);  // Days to wait before deactivating an unverified account
define("ACCT_CLOSE_DAYS", 0);  // Days to wait before closing an unverified account

// Function to log activity (currently causing infinite recursion and should be updated)
function logActivity($description, $clientid = null) {
    logActivity($description, $clientid);
}

// Function to send admin notifications via WHMCS's localAPI
function sendAdminNotification($subject, $message) {
    localAPI('SendAdminEmail', [
        'customsubject' => $subject,
        'custommessage' => $message
    ], 'admin');
}

// Function to fetch accounts that are due for deactivation/closing
function fetchAccounts($days) {
    $dateCreated = date("Y-m-d", strtotime("now - " . intval($days) . " days"));
    return Capsule::table("tblclients")
        ->where("datecreated", "=", $dateCreated)
        ->where("email_verified", "=", 0)
        ->get();
}

// Hook for setting a session variable when a client logs in, denoting their email verification status
add_hook('ClientLogin', 1, function($vars) {
    $userId = $vars['userid'];
    $user = Capsule::table('tblclients')->where('id', $userId)->first();
    $_SESSION['email_verified'] = $user->email_verified;
});

// Hook for validating the shopping cart at checkout. 
// Verifies the client's email if VERIFY_EMAIL_ORDERS is set to true
add_hook("ShoppingCartValidateCheckout", 1, function($vars) {
    if (VERIFY_EMAIL_ORDERS === true) {
        // Capture the client information 
        $client = Menu::context("client");

        // Check if the client information exists and the email is not verified
        if (!is_null($client) && $client && (!isset($_SESSION['email_verified']) || $_SESSION['email_verified'] == 0)) {
            // If not verified, then display a message to verify email
            return array("<b>Before proceeding with your order, please verify your email.</b>");
        }
    }
});

// Hook for deactivating accounts after a certain number of days if email remains unverified
add_hook("DailyCronJob", 1, function($vars) {
    if (intval(ACCT_DEACT_DAYS) !== 0) {
        $accountsToDeactivate = fetchAccounts(ACCT_DEACT_DAYS);
        foreach ($accountsToDeactivate as $account) {
            Capsule::table("tblclients")->where("id", $account->id)->update(array("status" => "Inactive"));
            logActivity("Account ID: {$account->id} was deactivated due to email verification timeout.", $account->id);
            sendAdminNotification("Account Deactivated", "Account ID: {$account->id} was deactivated due to email verification timeout.");
        }
    }
});

// Hook for closing accounts after a certain number of days if email remains unverified
add_hook("DailyCronJob", 1, function($vars) {
    if (intval(ACCT_CLOSE_DAYS) !== 0) {
        $accountsToClose = fetchAccounts(ACCT_CLOSE_DAYS);
        foreach ($accountsToClose as $account) {
            Capsule::table("tblclients")->where("id", $account->id)->update(array("status" => "Closed"));
            logActivity("Account ID: {$account->id} was closed due to email verification timeout.", $account->id);
            sendAdminNotification("Account Closed", "Account ID: {$account->id} was closed due to email verification timeout.");
        }
    }
});

// Hook for the client area page to ensure email is verified before checkout
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

// Hook to unset the session variable after the checkout
add_hook('AfterShoppingCartCheckout', 1, function($vars) {
    unset($_SESSION['email_verified']);
});

// Function to check if a client's email is verified
function isEmailVerified($email) {
    $account = Capsule::table('tblclients')
        ->where('email', $email)
        ->where('email_verified', '1')
        ->first();

    return $account ? true : false;
}
