<?php
require_once 'config/config.php';
require_once 'middleware/Auth.php';
require_once 'config/Database.php';

Auth::check();

$database = new Database();
$db = $database->connect();

$employer_id = $_SESSION['user_id'];

// Get detailed info about pending applications
$query = "SELECT 
            ja.id as application_id,
            ja.user_id as applicant_user_id,
            ja.status,
            ja.applied_at,
            jp.id as job_id,
            jp.title as job_title,
            jp.employer_id,
            u.username as applicant_username,
            CONCAT(u.first_name, ' ', u.last_name) as applicant_name
          FROM job_applications ja
          INNER JOIN job_postings jp ON ja.job_id = jp.id
          INNER JOIN users u ON ja.user_id = u.id
          WHERE jp.employer_id = :employer_id 
          AND ja.status = 'pending'
          ORDER BY ja.applied_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(':employer_id', $employer_id);
$stmt->execute();
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Pending Applications Debug</h2>";
echo "<p>Your User ID (Employer): " . $employer_id . "</p>";
echo "<p>Total Pending Applications: " . count($applications) . "</p>";
echo "<hr>";

if(count($applications) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Application ID</th><th>Job Title</th><th>Applicant</th><th>Applicant User ID</th><th>Applied Date</th></tr>";
    foreach($applications as $app) {
        echo "<tr>";
        echo "<td>" . $app['application_id'] . "</td>";
        echo "<td>" . htmlspecialchars($app['job_title']) . "</td>";
        echo "<td>" . htmlspecialchars($app['applicant_name']) . " (@" . htmlspecialchars($app['applicant_username']) . ")</td>";
        echo "<td>" . $app['applicant_user_id'] . "</td>";
        echo "<td>" . $app['applied_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No pending applications found.</p>";
}
?>
