# Post2Email
This is a small PHP script that acts as a proxy for sending emails using SMTP, based on a received HTTP post request. It can be used, for example, to send notifications by email from shell scripts. 

If you need notifications pushed to your smartphone, you want to have a look at more advanced solutions such as [NTFY](https://ntfy.sh/) or [Gotify](https://gotify.net/). But I am a email guy, tending to forget about push notifications popped up on my phone during the day. So I wrote this script – running on my DiskStation – in order to send email notifications from scripts in my homelab.

**Please note:** I've written the script with homelab usage only in mind. So if you place the script on a public server you may want to utilize extra measurements against unauthorized use. Maybe [HTTP Basic Auth](https://developer.mozilla.org/en-US/docs/Web/HTTP/Guides/Authentication) will be your friend.

## Installation
The script uses the [PHPMailer classes](https://github.com/PHPMailer/PHPMailer) for communicating with the SMTP server. PHPMailer is **not included** in this repository and has to be installed using Composer. So download the content of this repo to your machine, extract and `cd` into the directory, than install PHPMailer dependency with Composer:

```bash
composer install
```

(The command above may vary based on your host setup, see [Composer Basic Usage](https://getcomposer.org/doc/01-basic-usage.md). If you are using [DDEV](https://ddev.com/) you can run `ddev composer install`.)

## Setup
Once the PHPMailer dependency is installed, you have to configure your SMTP credentials. To do this, rename the provided `config.sample.php` to `config.php` and modify the values so they match your SMTP server. There you also set your preferred sender name and receiver email address:

```php
// Mail configuration for PHPMailer
$mailConfig = [
    'Host' => 'mail.example.com',      // SMTP server
    'SMTPAuth' => true,                // Enable SMTP authentication
    'Username' => 'yourUsername',
    'Password' => 'yourPassword',
    'SMTPSecure' => 'tls',             // 'ssl' or 'tls'
    'Port' => 587,                     // Usually 587 for TLS, 465 for SSL
    'From' => 'report@example.com',    // Sender email address
    'FromName' => 'Post2Mail',         // Sender name
    'To' => 'info@example.com',        // Receiver email address
];
```
Finally set a least one access key in the `$allowedKeys` array. They act as a kind of password and had to be provided as parameter on the URL:

```php
$allowedKeys = [
    'YOUR_s3cr3tK3y'
];
```

## Usage

To send an email, you have to do a HTTP POST to the URL of the script, supplemented by one of the previously defined keys. This POST has to be JSON, containing at least a `msg`(message) property. `sdr`(sender) and `prio`(priority) are optional. Here is an example of such a HTTP POST, using cURL from the command line:

```bash
curl --json '{"sdr":"Proxmox backup", "msg":"Rsync to offsite location finished.", "prio":0}' https://path-to-script/?key=YOUR_s3cr3tK3y
```
For the `prio`property, the following values are available at the moment (which basically changes the emoji color in the subject line of the email) and the `X-Priority` mail header:

| prio    | Description | Icon  | X-Priority
| ------- | ----------- | ----- | ----------
| 0       | none        | 🟢    | 5 (low)
| 1       | low         | 🟡    | 3 (normal)
| 2       | high        | 🟠    | 1 (high)
| 3       | critical    | 🔴    | 1 (high)


---

## Note on cURL before v7.82
The `--json` flag used in the example above is available sind cURL 7.82. To send JSON requests using older cURL versions, you may use this command:

```bash
curl -X POST https://path-to-script/?key=YOUR_s3cr3tK3y \
  -H "Content-Type: application/json" \
  -d '{"sdr":"Proxmox backup", "msg":"Rsync to offsite location finished.", "prio":0}'
```