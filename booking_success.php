<?php
// session, database and helpers (no login needed on this page)
require_once 'includes/public_init.php';

// the reference code from the url (?ref=RR-XXXXXX)
$ref = trim($_GET['ref'] ?? '');
// find that reservation, the JOIN also grabs the room info for the display
$s = $conn->prepare("SELECT r.*, rm.room_name, rm.building, rm.price_per_hour FROM reservations r
                     JOIN rooms rm ON rm.id = r.room_id WHERE r.reference_code = ?");
$s->bind_param("s", $ref); $s->execute();
$r = $s->get_result()->fetch_assoc();
// code not found, go back to the home page
if (!$r) { header('Location: index.php'); exit; }

require_once 'includes/public_header.php';
$hours = duration_hours($r['start_time'], $r['end_time']); // booking length in hours
?>
<div class="card mx-auto text-center" style="max-width:560px"><div class="card-body p-4 p-md-5">
  <i class="bi bi-check-circle-fill text-success" style="font-size:3rem"></i>
  <h3 class="mt-2 fw-bold">Request submitted!</h3>
  <p class="text-muted">Your reference code is:</p>
  <div class="ref-code mb-3"><?= e($r['reference_code']) ?></div>
  <p class="small text-muted">Save this code. Use it with your email on the <a href="check_booking.php">Check booking</a> page to see if it was approved.</p>
  <hr>
  <ul class="list-unstyled text-start mx-auto mb-3" style="max-width:360px">
    <li><strong>Room:</strong> <?= e($r['room_name']) ?> (<?= e($r['building']) ?>)</li>
    <li><strong>Date:</strong> <?= date('M d, Y', strtotime($r['reservation_date'])) ?></li>
    <li><strong>Time:</strong> <?= fmt_time($r['start_time']) ?> - <?= fmt_time($r['end_time']) ?> (<?= round($hours, 2) ?> hr)</li>
    <li><strong>Estimated total:</strong> <?= cost_label($r['start_time'], $r['end_time'], $r['price_per_hour']) ?></li>
    <li><strong>Status:</strong> <?= status_badge($r['status']) ?></li>
  </ul>
  <a href="index.php" class="btn btn-primary">Back to rooms</a>
</div></div>
<?php require_once 'includes/public_footer.php'; ?>
