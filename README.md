# PSSST!

A PHP-based single-user application to securely submit and retrieve information such as passwords via the web. No database required. Keep your secrets out of email and Slack!

## Installation

Upload the source files to a web server running PHP 8.2 or later, or use a local web server such as DDEV (configuration included) to test the application. If you're using a web server other than DDEV, make sure you set the `public` folder as the html root.

From the terminal:

`composer install`

`cp .env.example .env`

Edit the .env file to update the app settings. Include your Cloudflare Turnstile site key and Mailgun credentials (recommended but optional).

Generate an OPT key with the following terminal command:

`echo "TOTP_SECRET=\"$(LC_ALL=C tr -dc 'A-Z2-7' </dev/urandom | head -c 32)\"" >> .env`

## Usage

On first run, generate your TOTP authenticator secret at `/generate-totp`. This is a unique secret that is based on your admin email address and the TOTP_SECRET value in your .env file. You will use the dynamically generated 6 digit code to retrieve and delete secrets. Save this URL to your authenticator program.
___ 

### Extremly important note

The `generate-totp` endpoint is only available when the app is in "dev" mode. It is extremely important that you **only** run the publicly accessible site in "production" mode. Running a publicly accessible site in "dev" mode can expose your TOTP authentication credentials and therefore your secrets to the public. If you make this mistake, be sure to regenerate your `TOTP_SECRET` key and update your authenticator.
___

Create a new secret using the form at the main URL of your application. The secret will be saved in the `data` folder as plain text file prepended with a dot(.). These files are not accessible via the web.

A URL will be generated to retrieve the secret. If you've set up Mailgun, the URL will be emailed to the administrator. Otherwise, the URL will be displayed on the `/created/` page after the secret has been successfully saved.

The `retrieve` endpoint is protected by the TOTP authentication you set up earlier. Enter your code to view and delete your secrets.
