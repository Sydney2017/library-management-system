<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once '../includes/config.php';
require_once '../includes/database.php';

$response = ["success" => false, "message" => "Initial error"];

try {
    $database = new Database();
    $db = $database->getConnection();

    // Get ALL columns that exist in the books table
    $query = "SELECT * FROM books LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $sampleBook = $stmt->fetch(PDO::FETCH_ASSOC);
    $allColumns = array_keys($sampleBook);
    
    // Build query with available columns
    $availableColumns = [];
    $selectFields = [];
    
    // FIXED: Look for publication_date instead of publication_year
    $desiredColumns = ['id', 'title', 'author', 'isbn', 'genre', 'description', 'publication_date', 'status', 'available_copies'];
    
    foreach ($desiredColumns as $column) {
        if (in_array($column, $allColumns)) {
            $selectFields[] = $column;
            $availableColumns[] = $column;
        }
    }
    
    // Get all books with available columns
    $query = "SELECT " . implode(', ', $selectFields) . " FROM books ORDER BY title ASC";
    $stmt = $db->prepare($query);
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
                "imageUrl" => ''
            ];
            
            // Use real data if available, otherwise defaults
            $book["genre"] = isset($row['genre']) ? $row['genre'] : "General";
            $book["description"] = isset($row['description']) ? $row['description'] : "Available in library";
            
            // FIXED: Use publication_date column and extract year from DATE
            if (isset($row['publication_date']) && $row['publication_date'] !== null && $row['publication_date'] !== '') {
                // Extract year from date (YYYY-MM-DD)
                $publicationYear = date('Y', strtotime($row['publication_date']));
                $book["publicationYear"] = (int)$publicationYear;
            }
            // If no publication_date, don't set publicationYear field
            
            $book["totalCopies"] = isset($row['available_copies']) ? (int)$row['available_copies'] : 1;
            
            $books[] = $book;
        }
        
        $response["success"] = true;
        $response["message"] = count($books) . " books loaded";
        $response["debug"] = [
            "available_columns" => $availableColumns,
            "query_used" => $query
        ];
        $response["books"] = $books;
        
    } else {
        $response["message"] = "No books found in database";
    }

} catch (Exception $e) {
    $response["message"] = "Database error: " . $e->getMessage();
}

echo json_encode($response);
?>