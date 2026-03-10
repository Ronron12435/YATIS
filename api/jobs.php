<?php
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../models/JobPosting.php';
require_once __DIR__ . '/../models/JobApplication.php';
require_once __DIR__ . '/../utils/FileUpload.php';

header('Content-Type: application/json');

$database = new Database();
$db = $database->connect();

$method = $_SERVER['REQUEST_METHOD'];

try {
    if($method === 'POST') {
        // Handle file upload for job applications
        if(isset($_GET['action']) && $_GET['action'] === 'apply') {
            Auth::check();
            
            $job_id = $_POST['job_id'];
            $cover_letter = $_POST['cover_letter'] ?? '';
            
            // Check if user is trying to apply to their own job
            $query = "SELECT employer_id FROM job_postings WHERE id = :job_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':job_id', $job_id);
            $stmt->execute();
            $job = $stmt->fetch();
            
            if($job && $job['employer_id'] == Auth::getUserId()) {
                echo json_encode(['success' => false, 'message' => 'You cannot apply to your own job posting']);
                exit;
            }
            
            // Check if user already applied
            $query = "SELECT id FROM job_applications WHERE job_id = :job_id AND user_id = :user_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':job_id', $job_id);
            $user_id = Auth::getUserId();
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            
            if($stmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'You have already applied to this job']);
                exit;
            }
            
            if(!isset($_FILES['resume']) || $_FILES['resume']['error'] !== UPLOAD_ERR_OK) {
                echo json_encode(['success' => false, 'message' => 'Resume file is required']);
                exit;
            }
            
            $fileUpload = new FileUpload();
            $uploadResult = $fileUpload->uploadResume($_FILES['resume'], Auth::getUserId());
            
            if(!$uploadResult['success']) {
                echo json_encode($uploadResult);
                exit;
            }
            
            $application = new JobApplication($db);
            $application->job_id = $job_id;
            $application->user_id = Auth::getUserId();
            $application->resume_path = $uploadResult['path'];
            $application->cover_letter = $cover_letter;
            $application->status = 'pending';
            
            if($application->create()) {
                echo json_encode(['success' => true, 'message' => 'Application submitted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to submit application']);
            }
            exit;
        }
        
        Auth::check();
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        if($action === 'create') {
            Auth::checkRole(['business', 'employer', 'admin']);
            
            $job = new JobPosting($db);
            $job->employer_id = Auth::getUserId();
            $job->business_id = $data['business_id'] ?? null; // Link to specific business
            $job->title = $data['title'];
            $job->description = $data['description'];
            $job->requirements = $data['requirements'] ?? '';
            $job->salary_range = $data['salary'] ?? ''; // Updated field name
            $job->location = $data['location'];
            $job->job_type = $data['job_type'];
            $job->deadline = $data['deadline'] ?? null;
            $job->contact_email = $data['contact_email'] ?? '';
            $job->status = 'open';
            
            if($job->create()) {
                echo json_encode(['success' => true, 'message' => 'Job posted successfully', 'job_id' => $job->id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to post job']);
            }
        } elseif($action === 'toggle_status') {
            Auth::checkRole(['employer', 'admin']);
            
            $query = "UPDATE job_postings SET status = :status WHERE id = :id AND employer_id = :employer_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':id', $data['job_id']);
            $employer_id = Auth::getUserId();
            $stmt->bindParam(':employer_id', $employer_id);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Job status updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
        } elseif($action === 'update_application_status') {
            Auth::checkRole(['business', 'employer', 'admin']);
            
            $application_id = $data['application_id'];
            $status = $data['status'];
            
            // If accepting or rejecting, check if interview date has passed
            if($status === 'accepted' || $status === 'rejected') {
                $checkQuery = "SELECT interview_date, DATE(interview_date) as interview_date_only FROM job_applications WHERE id = :id";
                $checkStmt = $db->prepare($checkQuery);
                $checkStmt->bindParam(':id', $application_id);
                $checkStmt->execute();
                $application = $checkStmt->fetch();
                
                if(!$application || !$application['interview_date']) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'Please set an interview date first.'
                    ]);
                    exit;
                }
                
                // Check if interview date has arrived
                $interviewDate = new DateTime($application['interview_date_only']);
                $currentDate = new DateTime(date('Y-m-d'));
                
                if($currentDate < $interviewDate) {
                    echo json_encode([
                        'success' => false, 
                        'message' => 'You can only hire or reject applicants on or after the interview date.'
                    ]);
                    exit;
                }
            }
            
            $application = new JobApplication($db);
            $application->id = $application_id;
            
            if($application->updateStatus($status)) {
                $statusMessage = $status === 'accepted' ? 'Applicant hired successfully!' : 'Application status updated';
                echo json_encode(['success' => true, 'message' => $statusMessage]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
        } elseif($action === 'set_interview_date') {
            Auth::checkRole(['business', 'employer', 'admin']);
            
            $application_id = $data['application_id'];
            $interview_date = $data['interview_date'];
            
            $query = "UPDATE job_applications SET interview_date = :interview_date WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':interview_date', $interview_date);
            $stmt->bindParam(':id', $application_id);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Interview date set successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to set interview date']);
            }
        } elseif($action === 'update_job_status') {
            Auth::checkRole(['business', 'employer', 'admin']);
            
            $query = "UPDATE job_postings SET status = :status WHERE id = :id AND employer_id = :employer_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':status', $data['status']);
            $stmt->bindParam(':id', $data['job_id']);
            $employer_id = Auth::getUserId();
            $stmt->bindParam(':employer_id', $employer_id);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Job status updated']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update status']);
            }
        } elseif($action === 'delete_job') {
            Auth::checkRole(['business', 'employer', 'admin']);
            
            $query = "DELETE FROM job_postings WHERE id = :id AND employer_id = :employer_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $data['job_id']);
            $employer_id = Auth::getUserId();
            $stmt->bindParam(':employer_id', $employer_id);
            
            if($stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Job deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete job']);
            }
        }
    } elseif($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if($action === 'list') {
            Auth::check();
            $job = new JobPosting($db);
            $limit = intval($_GET['limit'] ?? 20);
            $offset = intval($_GET['offset'] ?? 0);
            $jobs = $job->getAll($limit, $offset);
            
            // Check if user has applied to each job and get business name
            $userId = Auth::getUserId();
            foreach($jobs as &$jobItem) {
                $query = "SELECT id FROM job_applications WHERE job_id = :job_id AND user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':job_id', $jobItem['id']);
                $stmt->bindParam(':user_id', $userId);
                $stmt->execute();
                $jobItem['has_applied'] = $stmt->fetch() ? true : false;
                
                // Get business name if business_id exists
                if($jobItem['business_id']) {
                    $query = "SELECT business_name, business_type FROM businesses WHERE id = :business_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':business_id', $jobItem['business_id']);
                    $stmt->execute();
                    $business = $stmt->fetch(PDO::FETCH_ASSOC);
                    if($business) {
                        $jobItem['business_name'] = $business['business_name'];
                        $jobItem['business_type'] = $business['business_type'];
                    }
                }
            }
            
            echo json_encode(['success' => true, 'jobs' => $jobs]);
        } elseif($action === 'details') {
            Auth::check();
            $job_id = $_GET['id'] ?? 0;
            $query = "SELECT jp.*, u.username as employer_name 
                      FROM job_postings jp
                      INNER JOIN users u ON jp.employer_id = u.id
                      WHERE jp.id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $job_id);
            $stmt->execute();
            $jobData = $stmt->fetch();
            
            if($jobData) {
                // Check if user has applied
                $userId = Auth::getUserId();
                $query = "SELECT id FROM job_applications WHERE job_id = :job_id AND user_id = :user_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':job_id', $job_id);
                $stmt->bindParam(':user_id', $userId);
                $stmt->execute();
                $jobData['has_applied'] = $stmt->fetch() ? true : false;
                
                // Get business name if business_id exists
                if($jobData['business_id']) {
                    $query = "SELECT business_name, business_type FROM businesses WHERE id = :business_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':business_id', $jobData['business_id']);
                    $stmt->execute();
                    $business = $stmt->fetch(PDO::FETCH_ASSOC);
                    if($business) {
                        $jobData['business_name'] = $business['business_name'];
                        $jobData['business_type'] = $business['business_type'];
                    }
                }
                
                echo json_encode(['success' => true, 'job' => $jobData]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Job not found']);
            }
        } elseif($action === 'my_applications') {
            Auth::check();
            $application = new JobApplication($db);
            $applications = $application->getByUserId(Auth::getUserId());
            
            // Add business name to each application
            foreach($applications as &$app) {
                if(isset($app['job_id'])) {
                    $query = "SELECT business_id FROM job_postings WHERE id = :job_id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(':job_id', $app['job_id']);
                    $stmt->execute();
                    $job = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if($job && $job['business_id']) {
                        $query = "SELECT business_name, business_type FROM businesses WHERE id = :business_id";
                        $stmt = $db->prepare($query);
                        $stmt->bindParam(':business_id', $job['business_id']);
                        $stmt->execute();
                        $business = $stmt->fetch(PDO::FETCH_ASSOC);
                        if($business) {
                            $app['business_name'] = $business['business_name'];
                            $app['business_type'] = $business['business_type'];
                        }
                    }
                }
            }
            
            echo json_encode(['success' => true, 'applications' => $applications]);
        } elseif($action === 'my_jobs') {
            Auth::check();
            Auth::checkRole(['business', 'employer', 'admin']);
            $job = new JobPosting($db);
            $jobs = $job->getByEmployerId(Auth::getUserId());
            
            // Add pending application count for each job
            foreach($jobs as &$jobItem) {
                $query = "SELECT COUNT(*) as pending_count 
                          FROM job_applications 
                          WHERE job_id = :job_id 
                          AND status = 'pending'";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':job_id', $jobItem['id']);
                $stmt->execute();
                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                $jobItem['pending_count'] = (int)$result['pending_count'];
            }
            
            echo json_encode(['success' => true, 'jobs' => $jobs]);
        } elseif($action === 'business_jobs') {
            // Public endpoint - get jobs for a specific business
            $business_id = $_GET['business_id'] ?? 0;
            
            // Get the employer_id (user_id) and business_type from the business
            $query = "SELECT user_id, business_type FROM businesses WHERE id = :business_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':business_id', $business_id);
            $stmt->execute();
            $business = $stmt->fetch();
            
            if($business) {
                // Get jobs that are specifically linked to this business ONLY
                // Do NOT show jobs with NULL business_id to avoid showing on wrong business types
                $query = "SELECT jp.*, u.username as employer_name 
                          FROM job_postings jp
                          INNER JOIN users u ON jp.employer_id = u.id
                          WHERE jp.business_id = :business_id
                          ORDER BY jp.created_at DESC";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':business_id', $business_id);
                $stmt->execute();
                $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode(['success' => true, 'jobs' => $jobs]);
            } else {
                echo json_encode(['success' => true, 'jobs' => []]);
            }
        } elseif($action === 'applicants') {
            Auth::check();
            Auth::checkRole(['business', 'employer', 'admin']);
            
            $job_id = $_GET['job_id'] ?? 0;
            
            if(!$job_id) {
                echo json_encode(['success' => false, 'message' => 'Job ID is required']);
                exit;
            }
            
            // Verify the job belongs to the current user (unless admin)
            if($_SESSION['role'] !== 'admin') {
                $query = "SELECT employer_id FROM job_postings WHERE id = :job_id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':job_id', $job_id);
                $stmt->execute();
                $job = $stmt->fetch();
                
                if(!$job || $job['employer_id'] != Auth::getUserId()) {
                    echo json_encode(['success' => false, 'message' => 'Unauthorized access to this job']);
                    exit;
                }
            }
            
            $application = new JobApplication($db);
            $applicants = $application->getByJobId($job_id);
            echo json_encode(['success' => true, 'applicants' => $applicants]);
        } elseif($action === 'pending_applications_count') {
            Auth::check();
            Auth::checkRole(['business', 'employer', 'admin']);
            
            // Get count of NEW (pending, not reviewed) applications for all jobs posted by this user
            // Once the employer views the applicants, they should be marked as 'reviewed'
            $query = "SELECT COUNT(*) as count 
                      FROM job_applications ja
                      INNER JOIN job_postings jp ON ja.job_id = jp.id
                      WHERE jp.employer_id = :employer_id 
                      AND ja.status = 'pending'";
            $stmt = $db->prepare($query);
            $employer_id = Auth::getUserId();
            $stmt->bindParam(':employer_id', $employer_id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'count' => (int)$result['count']]);
        } elseif($action === 'mark_applications_viewed') {
            Auth::check();
            Auth::checkRole(['business', 'employer', 'admin']);
            
            $job_id = $_GET['job_id'] ?? 0;
            
            // Verify the job belongs to the current user
            $query = "SELECT employer_id FROM job_postings WHERE id = :job_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':job_id', $job_id);
            $stmt->execute();
            $job = $stmt->fetch();
            
            if($job && $job['employer_id'] == Auth::getUserId()) {
                // Mark all pending applications for this job as 'reviewed'
                $query = "UPDATE job_applications 
                          SET status = 'reviewed' 
                          WHERE job_id = :job_id 
                          AND status = 'pending'";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':job_id', $job_id);
                $stmt->execute();
                
                echo json_encode(['success' => true, 'message' => 'Applications marked as viewed']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Unauthorized']);
            }
        }
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
