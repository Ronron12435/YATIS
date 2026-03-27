<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../middleware/Auth.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/models/Group.php';

header('Content-Type: application/json');

Auth::check();

$database = new Database();
$db = $database->connect();
$group = new Group($db);

$method = $_SERVER['REQUEST_METHOD'];

try {
    if($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        if($action === 'create') {
            $max_limit = Auth::isPremium() ? PREMIUM_GROUP_LIMIT : FREE_GROUP_LIMIT;
            $requested_limit = intval($data['member_limit']);
            
            if($requested_limit > $max_limit) {
                echo json_encode(['success' => false, 'message' => 'Member limit exceeds your plan limit']);
                exit;
            }
            
            $group->name = $data['name'];
            $group->description = $data['description'] ?? '';
            $group->creator_id = Auth::getUserId();
            $group->member_limit = $requested_limit;
            $group->privacy = $data['privacy'] ?? 'public';
            
            if($group->create()) {
                echo json_encode(['success' => true, 'message' => 'Group created successfully', 'group_id' => $group->id]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create group']);
            }
        } elseif($action === 'join') {
            $group_id = $data['group_id'] ?? 0;
            $user_id = Auth::getUserId();
            
            // Check if group exists and is public
            $checkQuery = "SELECT g.*, 
                          (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
                          FROM groups g WHERE g.id = :group_id";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindParam(':group_id', $group_id);
            $checkStmt->execute();
            $groupData = $checkStmt->fetch();
            
            if(!$groupData) {
                echo json_encode(['success' => false, 'message' => 'Group not found']);
                exit;
            }
            
            if($groupData['privacy'] === 'private') {
                echo json_encode(['success' => false, 'message' => 'This group is private']);
                exit;
            }
            
            if($groupData['member_count'] >= $groupData['member_limit']) {
                echo json_encode(['success' => false, 'message' => 'Group is full']);
                exit;
            }
            
            // Check if already a member
            $memberCheckQuery = "SELECT id FROM group_members WHERE group_id = :group_id AND user_id = :user_id";
            $memberCheckStmt = $db->prepare($memberCheckQuery);
            $memberCheckStmt->bindParam(':group_id', $group_id);
            $memberCheckStmt->bindParam(':user_id', $user_id);
            $memberCheckStmt->execute();
            
            if($memberCheckStmt->rowCount() > 0) {
                echo json_encode(['success' => false, 'message' => 'You are already a member']);
                exit;
            }
            
            // Add member
            $joinQuery = "INSERT INTO group_members (group_id, user_id, role) VALUES (:group_id, :user_id, 'member')";
            $joinStmt = $db->prepare($joinQuery);
            $joinStmt->bindParam(':group_id', $group_id);
            $joinStmt->bindParam(':user_id', $user_id);
            
            if($joinStmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Joined group successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to join group']);
            }
        } elseif($action === 'leave_group') {
            $group_id = $data['group_id'] ?? 0;
            $user_id = Auth::getUserId();
            
            // Check if user is a member
            $memberCheckQuery = "SELECT role FROM group_members WHERE group_id = :group_id AND user_id = :user_id";
            $memberCheckStmt = $db->prepare($memberCheckQuery);
            $memberCheckStmt->bindParam(':group_id', $group_id);
            $memberCheckStmt->bindParam(':user_id', $user_id);
            $memberCheckStmt->execute();
            $memberData = $memberCheckStmt->fetch();
            
            if(!$memberData) {
                echo json_encode(['success' => false, 'message' => 'You are not a member of this group']);
                exit;
            }
            
            // Check if user is the creator/admin
            $groupQuery = "SELECT creator_id FROM groups WHERE id = :group_id";
            $groupStmt = $db->prepare($groupQuery);
            $groupStmt->bindParam(':group_id', $group_id);
            $groupStmt->execute();
            $groupData = $groupStmt->fetch();
            
            if($groupData && $groupData['creator_id'] == $user_id) {
                // Check if there are other members
                $countQuery = "SELECT COUNT(*) FROM group_members WHERE group_id = :group_id";
                $countStmt = $db->prepare($countQuery);
                $countStmt->bindParam(':group_id', $group_id);
                $countStmt->execute();
                $memberCount = $countStmt->fetchColumn();
                
                if($memberCount > 1) {
                    echo json_encode(['success' => false, 'message' => 'As the group creator, you cannot leave while there are other members. Please transfer ownership or delete the group.']);
                    exit;
                }
            }
            
            // Remove member
            $leaveQuery = "DELETE FROM group_members WHERE group_id = :group_id AND user_id = :user_id";
            $leaveStmt = $db->prepare($leaveQuery);
            $leaveStmt->bindParam(':group_id', $group_id);
            $leaveStmt->bindParam(':user_id', $user_id);
            
            if($leaveStmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Left group successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to leave group']);
            }
        } elseif($action === 'remove_member') {
            $group_id = $data['group_id'] ?? 0;
            $member_id = $data['member_id'] ?? 0;
            $user_id = Auth::getUserId();
            
            // Check if current user is an admin of the group
            $adminCheckQuery = "SELECT role FROM group_members WHERE group_id = :group_id AND user_id = :user_id";
            $adminCheckStmt = $db->prepare($adminCheckQuery);
            $adminCheckStmt->bindParam(':group_id', $group_id);
            $adminCheckStmt->bindParam(':user_id', $user_id);
            $adminCheckStmt->execute();
            $adminData = $adminCheckStmt->fetch();
            
            if(!$adminData || $adminData['role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'Only admins can remove members']);
                exit;
            }
            
            // Check if target member exists and get their role
            $memberCheckQuery = "SELECT role FROM group_members WHERE group_id = :group_id AND user_id = :member_id";
            $memberCheckStmt = $db->prepare($memberCheckQuery);
            $memberCheckStmt->bindParam(':group_id', $group_id);
            $memberCheckStmt->bindParam(':member_id', $member_id);
            $memberCheckStmt->execute();
            $memberData = $memberCheckStmt->fetch();
            
            if(!$memberData) {
                echo json_encode(['success' => false, 'message' => 'Member not found in this group']);
                exit;
            }
            
            // Prevent removing other admins
            if($memberData['role'] === 'admin') {
                echo json_encode(['success' => false, 'message' => 'Cannot remove other admins from the group']);
                exit;
            }
            
            // Prevent admin from removing themselves (they should use leave instead)
            if($member_id == $user_id) {
                echo json_encode(['success' => false, 'message' => 'Use the Leave Group option to leave the group']);
                exit;
            }
            
            // Remove the member
            $removeQuery = "DELETE FROM group_members WHERE group_id = :group_id AND user_id = :member_id";
            $removeStmt = $db->prepare($removeQuery);
            $removeStmt->bindParam(':group_id', $group_id);
            $removeStmt->bindParam(':member_id', $member_id);
            
            if($removeStmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Member removed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove member']);
            }
        }
    } elseif($method === 'GET') {
        $action = $_GET['action'] ?? '';
        
        if($action === 'list') {
            // Get all public groups with member count
            $query = "SELECT g.*, u.username as creator_name,
                     (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
                     (SELECT COUNT(*) FROM group_members WHERE group_id = g.id AND user_id = :user_id) as is_member
                     FROM groups g
                     INNER JOIN users u ON g.creator_id = u.id
                     WHERE g.privacy = 'public'
                     ORDER BY g.created_at DESC";
            $stmt = $db->prepare($query);
            $user_id = Auth::getUserId();
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $groups = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'groups' => $groups]);
        } elseif($action === 'my_groups') {
            // Get groups where user is a member
            $query = "SELECT g.*, u.username as creator_name, gm.role as my_role,
                     (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count
                     FROM groups g
                     INNER JOIN group_members gm ON g.id = gm.group_id
                     INNER JOIN users u ON g.creator_id = u.id
                     WHERE gm.user_id = :user_id
                     ORDER BY g.created_at DESC";
            $stmt = $db->prepare($query);
            $user_id = Auth::getUserId();
            $stmt->bindParam(':user_id', $user_id);
            $stmt->execute();
            $groups = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'groups' => $groups]);
        } elseif($action === 'get_group_details') {
            // Get group details with members list
            if(empty($_GET['group_id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Group ID is required']);
                exit;
            }
            
            $group_id = intval($_GET['group_id']);
            $user_id = Auth::getUserId();
            
            // Verify user is a member of the group
            $memberCheckQuery = "SELECT COUNT(*) FROM group_members WHERE group_id = :group_id AND user_id = :user_id";
            $memberCheckStmt = $db->prepare($memberCheckQuery);
            $memberCheckStmt->bindParam(':group_id', $group_id);
            $memberCheckStmt->bindParam(':user_id', $user_id);
            $memberCheckStmt->execute();
            
            if($memberCheckStmt->fetchColumn() == 0) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'You are not a member of this group']);
                exit;
            }
            
            // Get group information
            $groupQuery = "SELECT g.*, u.username as creator_name
                          FROM groups g
                          INNER JOIN users u ON g.creator_id = u.id
                          WHERE g.id = :group_id";
            $groupStmt = $db->prepare($groupQuery);
            $groupStmt->bindParam(':group_id', $group_id);
            $groupStmt->execute();
            $groupData = $groupStmt->fetch(PDO::FETCH_ASSOC);
            
            if(!$groupData) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Group not found']);
                exit;
            }
            
            // Get all members with user information
            $membersQuery = "SELECT gm.user_id, gm.role, gm.joined_at,
                            u.username, u.first_name, u.last_name, u.profile_picture
                            FROM group_members gm
                            INNER JOIN users u ON gm.user_id = u.id
                            WHERE gm.group_id = :group_id
                            ORDER BY gm.role DESC, gm.joined_at ASC";
            $membersStmt = $db->prepare($membersQuery);
            $membersStmt->bindParam(':group_id', $group_id);
            $membersStmt->execute();
            $members = $membersStmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'group' => $groupData,
                'members' => $members,
                'member_count' => count($members)
            ]);
        }
    }
} catch(Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
