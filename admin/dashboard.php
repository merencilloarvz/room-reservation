<?php
// ADMIN dashboard: stat cards, pending requests inbox, and the room availability table.
// header.php includes auth.php, so only logged-in admins get past that line
require_once __DIR__ . '/../includes/header.php';

// the date shown in the availability table, default today.
// past dates are ALLOWED here because the admin can review old bookings
$date = $_GET['date'] ?? date('Y-m-d');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) $date = date('Y-m-d');

// the numbers for the stat cards.
// COUNT(*) c = count the rows and name the column "c",
// then fetch_assoc()['c'] grabs that one number
$total_rooms = $conn->query("SELECT COUNT(*) c FROM rooms")->fetch_assoc()['c'];
$available   = $conn->query("SELECT COUNT(*) c FROM rooms WHERE status='Available'")->fetch_assoc()['c'];
$pending     = $conn->query("SELECT COUNT(*) c FROM reservations WHERE status='Pending'")->fetch_assoc()['c'];

// today's bookings grouped by status. the two zeroes are the default,
// so the array always has both keys even if there are no rows yet
$today = ['Approved' => 0, 'Pending' => 0];
$q = $conn->query("SELECT status, COUNT(*) c FROM reservations WHERE reservation_date = CURDATE() AND status <> 'Cancelled' GROUP BY status");
while ($row = $q->fetch_assoc()) { $today[$row['status']] = $row['c']; } // fill in the real counts

// requests waiting for the admin to confirm (JOIN gets the room name and price)
$pending_list = $conn->query("SELECT r.*, rm.room_name, rm.price_per_hour FROM reservations r
    JOIN rooms rm ON rm.id = r.room_id
    WHERE r.status = 'Pending'
    ORDER BY r.reservation_date, r.start_time");

// all rooms
$rooms = $conn->query("SELECT * FROM rooms ORDER BY building, room_name");

// reservations on the chosen date (not cancelled), grouped by room.
// same idea as index.php, but here we also select guest_name because
// the admin is allowed to see who booked each slot
$booked = [];
$s = $conn->prepare("SELECT room_id, guest_name, start_time, end_time, status FROM reservations
                     WHERE reservation_date = ? AND status <> 'Cancelled' ORDER BY start_time");
$s->bind_param("s", $date);
$s->execute();
$res = $s->get_result();
while ($b = $res->fetch_assoc()) { $booked[$b['room_id']][] = $b; }

// the 4 stat cards: [label, number, small text, css class, icon].
// the foreach below prints each one, keeps the html short
$cards = [
  ['Total rooms',        $total_rooms, 'Across all buildings', '', 'bi-door-open'],
  ['Available now',      $available,   'Ready to book today', 'stat-green', 'bi-check-circle'],
  ['Reservations today', $today['Approved'] + $today['Pending'], $today['Approved'] . ' approved, ' . $today['Pending'] . ' pending', '', 'bi-calendar-check'],
  ['Needs your action',  $pending,     'Pending approval', 'stat-alert', 'bi-hourglass-split'],
];
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Dashboard</h1>
    <p class="page-sub"><?= date('l, F j, Y') ?></p>
  </div>
  <a href="rooms.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-door-open"></i> Manage rooms</a>
</div>

<?php // loop through $cards, one card each ?>
<div class="row g-3 mb-4">
<?php foreach ($cards as [$label, $n, $hint, $class, $icon]): ?>
  <div class="col-6 col-lg-3">
    <div class="card stat-card <?= $class ?> h-100"><div class="card-body d-flex justify-content-between align-items-start">
      <div>
        <div class="stat-label"><?= $label ?></div>
        <div class="stat-value"><?= $n ?></div>
        <div class="stat-hint"><?= $hint ?></div>
      </div>
      <div class="stat-icon"><i class="bi <?= $icon ?>"></i></div>
    </div></div>
  </div>
<?php endforeach; ?>
</div>

<?php // pending requests: the admin confirms or rejects them here ?>
<div class="card mb-4">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="section-title">Pending requests</span>
    <span class="pill pill-amber"><?= $pending ?> waiting</span>
  </div>
  <?php if ($pending_list->num_rows === 0): ?>
    <div class="empty-state"><i class="bi bi-check2-circle"></i>No pending requests. All caught up!</div>
  <?php endif; ?>
  <?php // one row per pending request ?>
  <?php while ($p = $pending_list->fetch_assoc()): ?>
    <div class="req-row">
      <div class="d-flex align-items-center gap-2">
        <span class="avatar"><?= e(initials($p['guest_name'])) ?></span>
        <div><div class="fw-semibold"><?= e($p['guest_name']) ?></div><div class="small text-muted"><?= e($p['contact']) ?></div></div>
      </div>
      <div>
        <div class="fw-semibold"><?= e($p['room_name']) ?></div>
        <div class="small text-muted"><?= date('M d, Y', strtotime($p['reservation_date'])) ?> &middot; <?= fmt_time($p['start_time']) ?> - <?= fmt_time($p['end_time']) ?></div>
      </div>
      <div class="small"><?= e($p['purpose']) ?><div class="text-muted">Est. <?= cost_label($p['start_time'], $p['end_time'], $p['price_per_hour']) ?></div></div>
      <div class="text-nowrap">
        <?php // back=dashboard makes reservation_status.php return here after the click ?>
        <form action="reservation_status.php" method="post" class="d-inline">
          <input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="back" value="dashboard">
          <input type="hidden" name="status" value="Approved">
          <button class="btn btn-sm btn-approve">Approve</button>
        </form>
        <form action="reservation_status.php" method="post" class="d-inline" onsubmit="return confirm('Reject this request?');">
          <input type="hidden" name="id" value="<?= $p['id'] ?>"><input type="hidden" name="back" value="dashboard">
          <input type="hidden" name="status" value="Cancelled">
          <button class="btn btn-sm btn-reject">Reject</button>
        </form>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<?php // room availability: a timeline per room, who booked it and when ?>
<div class="card">
  <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span class="section-title">Room availability</span>
    <form method="get" class="d-flex align-items-center gap-2">
      <label for="date" class="small text-muted fw-normal mb-0">Date</label>
      <input type="date" id="date" name="date" value="<?= e($date) ?>" class="form-control form-control-sm" onchange="this.form.submit()">
    </form>
  </div>
  <div class="table-responsive">
    <table class="table avail-table mb-0">
      <thead><tr>
        <th class="col-room">Room</th><th class="col-status">Status</th>
        <th class="col-timeline">
          <?php // hour labels, each one is placed with left: percent ?>
          <div class="axis"><?php foreach (axis_labels() as [$left, $label]): ?><span style="left:<?= $left ?>%"><?= $label ?></span><?php endforeach; ?></div>
        </th>
      </tr></thead>
      <tbody>
      <?php // $slots = the reservations of this room on the chosen date ?>
      <?php while ($r = $rooms->fetch_assoc()): $slots = $booked[$r['id']] ?? []; ?>
        <tr>
          <td><div class="fw-semibold"><?= e($r['room_name']) ?></div><div class="small text-muted"><?= e($r['building']) ?> &middot; <?= people_label($r['capacity']) ?></div></td>
          <td>
            <?php if ($r['status'] === 'Maintenance'): ?><span class="pill pill-red">Maintenance</span>
            <?php elseif ($slots): ?><span class="pill pill-amber">Booked</span>
            <?php else: ?><span class="pill pill-green">Available</span><?php endif; ?>
          </td>
          <td class="col-timeline">
            <div class="timeline">
              <?php // green = approved, dashed yellow = pending ?>
              <?php foreach ($slots as $b): ?>
                <div class="slot slot-<?= strtolower($b['status']) ?>" style="<?= slot_style($b['start_time'], $b['end_time']) ?>"
                     title="<?= e($b['guest_name']) ?>: <?= fmt_time($b['start_time']) ?> - <?= fmt_time($b['end_time']) ?> (<?= e($b['status']) ?>)"><?= e($b['guest_name']) ?></div>
              <?php endforeach; ?>
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
