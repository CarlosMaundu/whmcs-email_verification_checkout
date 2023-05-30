# WHMCS Email Verification Checkout

This code provides email verification functionality for the checkout process in WHMCS. It ensures that customers verify their email addresses before placing an order, preventing orders from being stored in the database until email verification is completed.

## Installation

1. Download the latest release of the WHMCS email verification checkout code.

2. Upload the file `email_verification_checkout.php` to the `includes/hooks/` directory in your WHMCS installation.

3. Open the `email_verification_checkout.php` file and set the configuration options according to your preferences:
   - `PREVENTUNVERIFIEDORDERS`: Set to `true` to prevent unverified accounts from placing orders. Set to `false` to accept orders regardless of email verification status.
   - `DEACTIVATEACCOUNTAFTERXDAYS`: Set the number of days to wait before deactivating unverified accounts. Set to `0` to disable this feature.
   - `CLOSEACCOUNTAFTERXDAYS`: Set the number of days to wait before setting unverified accounts as closed. Set to `0` to disable this feature.

4. Save the file.

5. Verify that your WHMCS installation has the necessary requirements, such as the WHMCS API and the Illuminate Database Capsule.

6. Run the daily cron job in WHMCS to handle account deactivation and closure according to the configured timeframes.

## Usage

Once the code is installed and configured, it will enforce email verification for the checkout process in WHMCS. Customers will be required to verify their email addresses before placing an order. Unverified accounts will be prevented from completing the checkout process.

## License

This code is released under the [MIT License](LICENSE).
