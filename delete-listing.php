<?php
// delete-listing.php
require_once 'includes/config.php';
requireLogin();

$id = (int)($_GET['id'] ?? 0);
$db = getDB();

$stmt = $db->prepare("SELECT user_id FROM listings WHERE listing_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) { header('Location: dashboard.php'); exit(); }

// Only owner or admin can delete
if ($row['user_id'] !== $_SESSION['user_id'] && !in_array($_SESSION['role'], ['admin','moderator'])) {
    header('Location: dashboard.php?error=unauthorized'); 
    exit();
}

// Use prepared statement to prevent SQL injection
$stmt = $db->prepare("UPDATE listings SET status='removed' WHERE listing_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

header('Location: dashboard.php?deleted=1');
exit();
?>