<?php

if (!defined("WHMCS"))
    die("Can't access the file directly!");

use WHMCS\View\Menu\Item as MenuItem;
use Illuminate\Database\Capsule\Manager as Capsule;

# Would you like to prevent unverified accounts from placing orders? Set it to false to accept orders
define("PREVENTUNVERIFIEDORDERS", true);
# How many days to wait before deactivating the unverified account, set 0 to deactivate this feature
define("DEACTIVATEACCOUNTAFTERXDAYS", 0);
# How many days to wait before setting the unverified account as closed, set 0 to disable this feature
define("CLOSEACCOUNTAFTERXDAYS", 0);

# Orders will not be completed if the email is not verified.
add_hook("ShoppingCartValidateCheckout", 1, function($vars) {
    if (PREVENTUNVERIFIEDORDERS === true) {
        // Get the client data
        $client = Menu::context("client");
        // Verify if the client is logged in and if it is found
        if (!is_null($client) && $client) {
            // Check if the email is not verified
            if ($client->isEmailAddressVerified() == false) {
                // Check if the cart contains products
                if ($vars['cart']->getCount() > 0) {
                    // Clear the cart to prevent order placement
                    $vars['cart']->clear();
                }
                return array("<b>Make sure you verify your email before proceeding with the order.</b>");
            }
        }
    }
});

# Deactivate unverified account after x days
add_hook("DailyCronJob", 1, function($vars) {
    if (intval(DEACTIVATEACCOUNTAFTERXDAYS) !== 0) {
        $dateCreated = date("Y-m-d", strtotime("now - " . intval(DEACTIVATEACCOUNTAFTERXDAYS) . " days"));
        $getAccounts = Capsule::table("tblclients")->where("datecreated", "=", $dateCreated)->where("email_verified", "=", 0);
        foreach ($getAccounts->get() as $account) {
            Capsule::table("tblclients")->where("id", $account->id)->update(array("status" => "Inactive"));
        }
    }
});

# Close unverified accounts after X days
add_hook("DailyCronJob", 1, function($vars) {
    if (intval(CLOSEACCOUNTAFTERXDAYS) !== 0) {
        $dateCreated = date("Y-m-d", strtotime("now - " . intval(CLOSEACCOUNTAFTERXDAYS) . " days"));
        $getAccounts = Capsule::table("tblclients")->where("datecreated", "=", $dateCreated)->where("email_verified", "=", 0);
        foreach ($getAccounts->get() as $account) {
            Capsule::table("tblclients")->where("id", $account->id)->update(array("status" => "Closed"));
        }
    }
});

add_hook('ClientAreaPage', 1, function($vars) {
    $currentPage = $vars['filename'];

    // Check if the current page is the checkout page
    if ($currentPage == 'cart') {
        if ($_POST['custtype'] == 'account') {
            // Email verification is required for new accounts
            if (!isEmailVerified($vars['clientsdetails']['email'])) {
                redir("a=checkout&emailverification=required");
            }
        } else {
            // Email verification is required for existing accounts
            if (!isEmailVerified($vars['clientsdetails']['email'])) {
                redir("a=checkout&emailverification=required");
            }
        }
    }
});

function isEmailVerified($email) {
    $result = select_query('tblclients', 'id', array('email' => $email, 'email_verified' => '1'));
    $data = mysql_fetch_array($result);
    return $data['id'] ? true : false;
}
