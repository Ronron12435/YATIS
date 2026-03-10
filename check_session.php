<?php
session_start();

echo "<h1>Session Debug</h1>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n\n";
echo "Session Data:\n";
print_r($_SESSION);
echo "</pre>";

echo "<hr>";
echo "<h2>Cookies:</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<hr>";
echo "<p><a href='dashboard.php'>Go to Dashboard</a> | <a href='index.php'>Logout</a></p>";
?>
