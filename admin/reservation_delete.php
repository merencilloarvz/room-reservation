<?php
// admin only
require_once __DIR__ . '/../includes/auth.php';

// ADMIN delete reservation. POST only so it can't be triggered by a crafted link
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    // delete the reservation with that id
    $d = $conn->prepare("DELETE FROM reservations WHERE id = ?");
    $d->bind_param("i", $id);
    $d->execute();
    flash('success', 'Reservation deleted.');
}
// back to the reservation list
header('Location: reservations.php');
exit;
