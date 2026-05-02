<?php
require_once __DIR__ . '/includes/helpers.php';

echo "<h2>SMTP Connection Test</h2>";
echo "Connecting to " . MAIL_HOST . ":" . MAIL_PORT . "...<br>";

$test_email = "test@example.com"; // Change this to your own email if you want to see it in Mailtrap
$success = send_email($test_email, "Test Email from " . APP_NAME, "
    <h3>SMTP Test Successful!</h3>
    <p>This is a test email sent from your Library System using Mailtrap.</p>
    <p>Time: " . date('Y-m-d H:i:s') . "</p>
");

if ($success) {
    echo "<p style='color:green; font-weight:bold;'>SUCCESS! Check your Mailtrap inbox.</p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>FAILED! Could not send email.</p>";
    echo "<p>Possible issues:</p>
          <ul>
            <li>Invalid credentials in config.php</li>
            <li>Firewall blocking port " . MAIL_PORT . "</li>
            <li>Mailtrap sandbox limit reached</li>
          </ul>";
}
