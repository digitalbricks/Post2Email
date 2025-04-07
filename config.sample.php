<?php
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


$allowedKeys = [
    'YOUR_s3cr3tK3y'
];