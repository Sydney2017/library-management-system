<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function isLibrarian() {
    return isset($_SESSION['role']) && ($_SESSION['role'] === 'librarian' || $_SESSION['role'] === 'admin');
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function debugFineCalculation($due_date) {
    $today = time();
    $due = strtotime($due_date);
    
    echo "Due Date: " . date('Y-m-d', $due) . "<br>";
    echo "Today: " . date('Y-m-d', $today) . "<br>";
    echo "Days Late: " . floor(($today - $due) / (60 * 60 * 24)) . "<br>";
    echo "Fine: R" . calculateFine($due_date) . "<br>";
}


// Notification Functions
// function getNewReservationsCount($db) {
//     // Check last view time (reset daily)
//     $last_view_date = $_SESSION['reservations_last_viewed'] ?? null;
//     $today = date('Y-m-d');
    
//     // If already viewed today, no notifications
//     if ($last_view_date === $today) {
//         return 0;
//     }
    
//     $query = "SELECT COUNT(*) as count FROM reservations 
//               WHERE status = 'active' 
//               AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
//     $stmt = $db->query($query);
//     $result = $stmt->fetch(PDO::FETCH_ASSOC);
//     return $result['count'];
// }

// function markReservationsAsViewed() {
//     $_SESSION['reservations_last_viewed'] = date('Y-m-d');
// }


// Notification Functions
function getNewReservationsCount($db) {
    // Get the last time admin viewed reservations
    $last_view_time = $_SESSION['reservations_last_viewed'] ?? null;
    
    // If never viewed, show all recent reservations
    if (!$last_view_time) {
        $query = "SELECT COUNT(*) as count FROM reservations 
                  WHERE status = 'active' 
                  AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $stmt = $db->query($query);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'];
    }
    
    // If viewed before, only show reservations made AFTER last view
    $query = "SELECT COUNT(*) as count FROM reservations 
              WHERE status = 'active' 
              AND created_at > :last_view_time";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':last_view_time', $last_view_time);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['count'];
}

function markReservationsAsViewed() {
    // Store the exact time when reservations were viewed
    $_SESSION['reservations_last_viewed'] = date('Y-m-d H:i:s');
}

// Notification functuions

// function getMemberReservationCount($member_id, $db) {
//     $query = "SELECT COUNT(*) as count FROM reservations 
//               WHERE member_id = :member_id AND status = 'active'";
//     $stmt = $db->prepare($query);
//     $stmt->bindParam(':member_id', $member_id);
//     $stmt->execute();
//     $result = $stmt->fetch(PDO::FETCH_ASSOC);
//     return $result['count'];
// }

// function getMemberActiveLoansCount($member_id, $db) {
//     $query = "SELECT COUNT(*) as count FROM book_loans 
//               WHERE member_id = :member_id AND status = 'active'";
//     $stmt = $db->prepare($query);
//     $stmt->bindParam(':member_id', $member_id);
//     $stmt->execute();
//     $result = $stmt->fetch(PDO::FETCH_ASSOC);
//     return $result['count'];
// }

// function getMemberPendingFinesCount($member_id, $db) {
//     $query = "SELECT COUNT(*) as count FROM fines 
//               WHERE member_id = :member_id AND status = 'pending'";
//     $stmt = $db->prepare($query);
//     $stmt->bindParam(':member_id', $member_id);
//     $stmt->execute();
//     $result = $stmt->fetch(PDO::FETCH_ASSOC);
//     return $result['count'];
// }

// // For librarians/admins
// function getOverdueLoansCount($db) {
//     $query = "SELECT COUNT(*) as count FROM book_loans 
//               WHERE status = 'active' AND due_date < CURDATE()";
//     $stmt = $db->prepare($query);
//     $stmt->execute();
//     $result = $stmt->fetch(PDO::FETCH_ASSOC);
//     return $result['count'];
// }

// function getPendingReservationsCount($db) {
//     $query = "SELECT COUNT(*) as count FROM reservations 
//               WHERE status = 'active'";
//     $stmt = $db->prepare($query);
//     $stmt->execute();
//     $result = $stmt->fetch(PDO::FETCH_ASSOC);
//     return $result['count'];
// }
?>