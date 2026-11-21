<?php
// admin only
require_once __DIR__ . '/../includes/auth.php';

// ADMIN approve/reject endpoint. the dashboard AND the reservations list both
// post here (small forms with hidden id + status inputs), and this page
// sends them back to wherever they came from
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $status = $_POST['status'] ?? '';
    // whitelist: only Approved / Cancelled can be set, anything else is ignored
    if (in_array($status, ['Approved', 'Cancelled'])) {
        // change the status of that reservation
        $s = $conn->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $s->bind_param("si", $status, $id);
        $s->execute();
        flash('success', "Reservation marked as $status.");
    }
}
// go back to the page where the button was clicked (dashboard or reservation list)
$back = (($_POST['back'] ?? '') === 'dashboard') ? 'dashboard.php' : 'reservations.php';
header('Location: ' . $back);
exit;
