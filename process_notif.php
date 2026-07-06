<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['action']) && $_GET['action'] == 'read' && isset($_GET['id'])) {
    $notif_id = (int)$_GET['id'];
    $user_id = (int)$_SESSION['user_id'];

    $db = new SQLite3('academies.db');
    $stmt = $db->prepare("UPDATE notifications SET status = 'read' WHERE id = :id AND user_id = :uid");
    $stmt->bindValue(':id', $notif_id, SQLITE3_INTEGER);
    $stmt->bindValue(':uid', $user_id, SQLITE3_INTEGER);
    $stmt->execute();
    $db->close();
}

$referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php';
header('Location: ' . $referer);
exit;
