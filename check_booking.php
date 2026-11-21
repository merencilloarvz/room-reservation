<?php
// session, database and helpers (no login needed on this page)
require_once 'includes/public_init.php';

// $r = the booking if found. $searched = has the form been submitted yet
$r = null; $searched = false;
// form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searched = true;
    // codes are stored uppercase, so uppercase the input too
    // (this way rr-abc123 and RR-ABC123 both work)
    $ref   = strtoupper(trim($_POST['ref'] ?? ''));
    $email = trim($_POST['email'] ?? '');
    // privacy: the code AND the matching email are BOTH required.
    // knowing only one of them isn't enough to view someone's booking
    $s = $conn->prepare("SELECT r.*, rm.room_name, rm.building, rm.price_per_hour FROM reservations r
                         JOIN rooms rm ON rm.id = r.room_id WHERE r.reference_code = ? AND r.email = ?");
    $s->bind_param("ss", $ref, $email); $s->execute();
    $r = $s->get_result()->fetch_assoc();
}
require_once 'includes/public_header.php';
?>
<div class="mx-auto" style="max-width:560px">
  <div class="page-head"><h1 class="page-title">Check your booking</h1></div>
  <div class="card mb-3"><div class="card-body p-4">
    <form method="post" class="row g-3">
      <div class="col-12"><label class="form-label">Reference code</label>
        <input type="text" name="ref" class="form-control" placeholder="RR-XXXXXX" required></div>
      <div class="col-12"><label class="form-label">Email used when booking</label>
        <input type="email" name="email" class="form-control" required></div>
      <div class="col-12"><button class="btn btn-primary">Check</button></div>
    </form>
  </div></div>

  <?php // searched but nothing found = warning. found = show the booking details ?>
  <?php if ($searched && !$r): ?>
    <div class="alert alert-warning">No booking found. Please check your reference code and email.</div>
  <?php elseif ($r): ?>
    <div class="card"><div class="card-body p-4">
      <div class="d-flex justify-content-between align-items-center"><h5 class="mb-0 fw-bold"><?= e($r['reference_code']) ?></h5><?= status_badge($r['status']) ?></div>
      <hr>
      <p class="mb-1"><strong>Room:</strong> <?= e($r['room_name']) ?> (<?= e($r['building']) ?>)</p>
      <p class="mb-1"><strong>Date:</strong> <?= date('M d, Y', strtotime($r['reservation_date'])) ?></p>
      <p class="mb-1"><strong>Time:</strong> <?= fmt_time($r['start_time']) ?> - <?= fmt_time($r['end_time']) ?></p>
      <p class="mb-1"><strong>Purpose:</strong> <?= e($r['purpose']) ?></p>
      <p class="mb-0"><strong>Estimated total:</strong> <?= cost_label($r['start_time'], $r['end_time'], $r['price_per_hour']) ?></p>
    </div></div>
  <?php endif; ?>
</div>
<?php require_once 'includes/public_footer.php'; ?>
