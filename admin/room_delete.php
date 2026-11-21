<?php
// admin only
require_once __DIR__ . '/../includes/auth.php';

// ADMIN delete room. POST only, so it can't be triggered by just visiting a link
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);

    // guard: if the room still has reservations we DON'T delete it.
    // deleting would leave the old bookings pointing at a room that no longer
    // exists. the advice to the admin is to set it to Maintenance instead
    $c = $conn->prepare("SELECT COUNT(*) c FROM reservations WHERE room_id = ?");
    $c->bind_param("i", $id); $c->execute();
    if ($c->get_result()->fetch_assoc()['c'] > 0) {
        // flash() saves a message for the next page
        flash('warning', 'Cannot delete: this room still has reservations. Set it to Maintenance instead.');
    } else {
        // no reservations, safe to delete
        $d = $conn->prepare("DELETE FROM rooms WHERE id = ?");
        $d->bind_param("i", $id);
        $d->execute();
        flash('success', 'Room deleted.');
    }
}
// back to the room list
header('Location: rooms.php');
exit;
