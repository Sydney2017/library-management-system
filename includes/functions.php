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

function calculateFine($due_date) {
    $today = time();
    $due = strtotime($due_date);
    $days_late = floor(($today - $due) / (60 * 60 * 24));
    
    if ($days_late > 0) {
        return $days_late * 10; // R10 per day
    }
    return 0;
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