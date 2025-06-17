<?php
session_start();
require_once "config/database.php";

// Check if user is logged in
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    http_response_code(401);
    echo json_encode(['error' => 'Please login to vote']);
    exit;
}

// Check if required parameters are provided
if(!isset($_POST['answer_id']) || !isset($_POST['vote_type'])){
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

$answer_id = (int)$_POST['answer_id'];
$vote_type = $_POST['vote_type'];
$user_id = $_SESSION['id'];

// Validate vote type
if(!in_array($vote_type, ['like', 'dislike'])){
    http_response_code(400);
    echo json_encode(['error' => 'Invalid vote type']);
    exit;
}

// Check if answer exists
$check_sql = "SELECT 1 FROM answers WHERE id = ?";
if($stmt = mysqli_prepare($conn, $check_sql)){
    mysqli_stmt_bind_param($stmt, "i", $answer_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if(mysqli_stmt_num_rows($stmt) == 0){
        http_response_code(404);
        echo json_encode(['error' => 'Answer not found']);
        exit;
    }
    mysqli_stmt_close($stmt);
}

// Check if user has already voted
$check_vote_sql = "SELECT vote_type FROM answer_votes WHERE answer_id = ? AND user_id = ?";
if($stmt = mysqli_prepare($conn, $check_vote_sql)){
    mysqli_stmt_bind_param($stmt, "ii", $answer_id, $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existing_vote = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
}

// Begin transaction
mysqli_begin_transaction($conn);

try {
    if($existing_vote){
        // If user is voting the same way, remove the vote
        if($existing_vote['vote_type'] === $vote_type){
            $delete_sql = "DELETE FROM answer_votes WHERE answer_id = ? AND user_id = ?";
            if($stmt = mysqli_prepare($conn, $delete_sql)){
                mysqli_stmt_bind_param($stmt, "ii", $answer_id, $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        } else {
            // If user is changing their vote, update it
            $update_sql = "UPDATE answer_votes SET vote_type = ? WHERE answer_id = ? AND user_id = ?";
            if($stmt = mysqli_prepare($conn, $update_sql)){
                mysqli_stmt_bind_param($stmt, "sii", $vote_type, $answer_id, $user_id);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }
    } else {
        // Insert new vote
        $insert_sql = "INSERT INTO answer_votes (answer_id, user_id, vote_type) VALUES (?, ?, ?)";
        if($stmt = mysqli_prepare($conn, $insert_sql)){
            mysqli_stmt_bind_param($stmt, "iis", $answer_id, $user_id, $vote_type);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    // Get updated vote counts
    $counts_sql = "SELECT 
        SUM(CASE WHEN vote_type = 'like' THEN 1 ELSE 0 END) as likes,
        SUM(CASE WHEN vote_type = 'dislike' THEN 1 ELSE 0 END) as dislikes
        FROM answer_votes 
        WHERE answer_id = ?";
    
    if($stmt = mysqli_prepare($conn, $counts_sql)){
        mysqli_stmt_bind_param($stmt, "i", $answer_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $counts = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }

    // Get user's current vote
    $user_vote_sql = "SELECT vote_type FROM answer_votes WHERE answer_id = ? AND user_id = ?";
    if($stmt = mysqli_prepare($conn, $user_vote_sql)){
        mysqli_stmt_bind_param($stmt, "ii", $answer_id, $user_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user_vote = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);
    }

    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'likes' => (int)$counts['likes'],
        'dislikes' => (int)$counts['dislikes'],
        'user_vote' => $user_vote ? $user_vote['vote_type'] : null
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while processing your vote']);
}
?> 