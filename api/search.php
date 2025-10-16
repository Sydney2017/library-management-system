<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once '../includes/config.php';
require_once '../includes/database.php';

$response = ["success" => false, "message" => "Initial error"];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get search query from GET parameter
    $searchQuery = isset($_GET['query']) ? $_GET['query'] : '';
    
    if (empty($searchQuery)) {
        $response["message"] = "Search query is required";
        echo json_encode($response);
        exit();
    }

    // Check if genre column exists
    $checkQuery = "SHOW COLUMNS FROM books LIKE 'genre'";
    $checkStmt = $db->prepare($checkQuery);
    $checkStmt->execute();
    $genreColumnExists = $checkStmt->rowCount() > 0;

    // Build search query based on available columns
    if ($genreColumnExists) {
        // Search in title, author, genre, or ISBN
        $query = "SELECT id, title, author, isbn, genre, description, publication_year, status 
                  FROM books 
                  WHERE title LIKE :search 
                     OR author LIKE :search 
                     OR genre LIKE :search 
                     OR isbn LIKE :search
                  ORDER BY title ASC";
    } else {
        // Search only in title, author, or ISBN (genre column doesn't exist)
        $query = "SELECT id, title, author, isbn, description, publication_year, status 
                  FROM books 
                  WHERE title LIKE :search 
                     OR author LIKE :search 
                     OR isbn LIKE :search
                  ORDER BY title ASC";
    }
    
    $stmt = $db->prepare($query);
    $searchTerm = "%" . $searchQuery . "%";
    $stmt->bindParam(':search', $searchTerm);
    $stmt->execute();

    $books = [];
    
    if ($stmt->rowCount() > 0) {
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $book = [
                "id" => (int)$row['id'],
                "title" => $row['title'],
                "author" => $row['author'],
                "isbn" => $row['isbn'],
                "available" => $row['status'] === 'available',
                "imageUrl" => '',
                "publicationYear" => (int)($row['publication_year'] ?? 2023)
            ];
            
            // Use real genre if available, otherwise default
            if ($genreColumnExists && !empty($row['genre'])) {
                $book["genre"] = $row['genre'];
            } else {
                $book["genre"] = "General";
            }
            
            // Use real description if available
            if (isset($row['description']) && !empty($row['description'])) {
                $book["description"] = $row['description'];
            } else {
                $book["description"] = "Available in our library collection";
            }
            
            $books[] = $book;
        }
        
        $response["success"] = true;
        $response["message"] = count($books) . " books found for '" . $searchQuery . "'";
        $response["books"] = $books;
        
    } else {
        $response["message"] = "No books found for '" . $searchQuery . "'";
    }

} catch (Exception $e) {
    $response["message"] = "Database error: " . $e->getMessage();
}

echo json_encode($response);
?>