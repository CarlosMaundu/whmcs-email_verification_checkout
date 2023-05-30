# WHMCS Email Verification Checkout

This code provides email verification functionality for the checkout process in WHMCS. It ensures that customers verify their email addresses before placing an order. The order prevention mechanism works by clearing the cart if the customer's email is not verified. Furthermore, it manages unverified accounts by setting them as inactive or closed after a specified number of days.

## Installation

1. Download the latest release of the WHMCS email verification checkout code.

2. Upload the file `email_verification_checkout.php` to the `includes/hooks/` directory in your WHMCS installation.

3. Open the `email_verification_checkout.php` file and set the configuration options according to your preferences:
   - `VERIFY_EMAIL_ORDERS`: Set to `true` to prevent unverified accounts from placing orders. Set to `false` to accept orders regardless of email verification status.
   - `ACCT_DEACT_DAYS`: Set the number of days to wait before deactivating unverified accounts. Set to `0` to disable this feature.
   - `ACCT_CLOSE_DAYS`: Set the number of days to wait before setting unverified accounts as closed. Set to `0` to disable this feature.

4. Save the file.

5. Ensure that your WHMCS installation has the necessary requirements, such as the WHMCS API and the Illuminate Database Capsule.

6. Run the daily cron job in WHMCS to handle account deactivation and closure according to the configured timeframes.

## Usage

Once the code is installed and configured, it will enforce email verification for the checkout process in WHMCS. Customers will be required to verify their email addresses before placing an order. Unverified accounts will be prevented from completing the checkout process, and their cart will be cleared.

For unverified accounts, after the number of days specified in `ACCT_DEACT_DAYS`, the account status will be set to 'Inactive'. If `ACCT_CLOSE_DAYS` is also set, then after the specified number of days, the account status will be set to 'Closed'.

## License

This code is released under the [MIT License](LICENSE).

## Updates

In the updated version, the script is using hooks to prevent unverified users from placing orders, deactivating unverified accounts after a specified number of days, and closing unverified accounts after a certain number of days. An additional hook for email verification during checkout is also included. Moreover, it includes additional functions for logging activity, sending admin notifications, fetching accounts for closing or deactivating, and checking if an email is verified.

This version also leverages WHMCS's Illuminate Database Capsule for making database queries, providing a more expressive, fluent interface to creating and running database queries.
