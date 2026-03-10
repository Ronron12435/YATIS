<?php
require_once 'config/config.php';
require_once 'middleware/Auth.php';
require_once 'config/Database.php';

Auth::check();

$database = new Database();
$db = $database->connect();

echo "<h2>Profile Visitor Tracking Test</h2>";
echo "<p>Current User ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>Username: " . $_SESSION['username'] . "</p>";
echo "<hr>";

// Check if profile_visits table exists
try {
    $stmt = $db->query("SHOW TABLES LIKE 'profile_visits'");
    if($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✓ profile_visits table exists</p>";
    } else {
        echo "<p style='color: red;'>✗ profile_visits table does NOT exist</p>";
    }
} catch(Exception $e) {
    echo "<p style='color: red;'>Error checking table: " . $e->getMessage() . "</p>";
}

// Show all profile visits
echo "<h3>All Profile Visits in Database:</h3>";
try {
    $stmt = $db->query("
        SELECT 
            pv.id,
            pv.visitor_id,
            pv.visited_user_id,
            pv.visit_time,
            v.username as visitor_username,
            u.username as visited_username
        FROM profile_visits pv
        LEFT JOIN users v ON pv.visitor_id = v.id
        LEFT JOIN users u ON pv.visited_user_id = u.id
        ORDER BY pv.visit_time DESC
        LIMIT 20
    ");
    $visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(count($visits) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Visitor</th><th>Visited User</th><th>Visit Time</th></tr>";
        foreach($visits as $visit) {
            echo "<tr>";
            echo "<td>" . $visit['id'] . "</td>";
            echo "<td>" . $visit['visitor_username'] . " (ID: " . $visit['visitor_id'] . ")</td>";
            echo "<td>" . $visit['visited_username'] . " (ID: " . $visit['visited_user_id'] . ")</td>";
            echo "<td>" . $visit['visit_time'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No visits recorded yet.</p>";
    }
} catch(Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

// Show visitors for current user
echo "<h3>Visitors to My Profile (Last 24 Hours):</h3>";
try {
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.username,
            u.first_name,
            u.last_name,
            pv.visit_time
        FROM profile_visits pv
        JOIN users u ON pv.visitor_id = u.id
        WHERE pv.visited_user_id = ? 
        AND pv.visit_time >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
        ORDER BY pv.visit_time DESC
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $visitors = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(count($visitors) > 0) {
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>Username</th><th>Name</th><th>Visit Time</th></tr>";
        foreach($visitors as $visitor) {
            echo "<tr>";
            echo "<td>" . $visitor['username'] . "</td>";
            echo "<td>" . $visitor['first_name'] . " " . $visitor['last_name'] . "</td>";
            echo "<td>" . $visitor['visit_time'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No visitors in the last 24 hours.</p>";
    }
} catch(Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
