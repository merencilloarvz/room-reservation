<?php
// ADMIN reservations list with search + filters (status, date)
require_once __DIR__ . '/../includes/header.php';

// values from the filter form. q = search word, status/date = optional filters
$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$date   = $_GET['date'] ?? '';

// the query is built piece by piece because the filters are optional.
// the ? placeholders stay in the sql, the real values go into $params
$sql = "SELECT r.*, rm.room_name, rm.price_per_hour FROM reservations r
        JOIN rooms rm ON rm.id = r.room_id WHERE (r.guest_name LIKE ? OR rm.room_name LIKE ? OR r.purpose LIKE ? OR r.reference_code LIKE ?)";
// % = wildcard, matches the word anywhere in the text
$like = "%$q%";
// "ssss" = 4 string values, one per ?. $types and $params must always stay in sync
$types = "ssss"; $params = [$like, $like, $like, $like];

// add each filter ONLY if the admin actually picked it,
// each one adds one more ? (one extra "s" + one extra value)
if (in_array($status, ['Pending', 'Approved', 'Cancelled'])) { $sql .= " AND r.status = ?"; $types .= "s"; $params[] = $status; }
if ($date !== '') { $sql .= " AND r.reservation_date = ?"; $types .= "s"; $params[] = $date; }

$sql .= " ORDER BY r.reservation_date DESC, r.start_time"; // newest date first

$stmt = $conn->prepare($sql);
// the ... (spread) unpacks the array into separate arguments.
// this way the query shape can change but it's STILL a prepared statement = still safe
$stmt->bind_param($types, ...$params);
$stmt->execute();
$rows = $stmt->get_result();
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Reservations</h1>
    <p class="page-sub">Requests from clients. Approve, reject or edit them here.</p>
  </div>
</div>

<form class="row g-2 filter-bar mx-0 align-items-center" method="get">
  <div class="col-md-4"><input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Search name, room, purpose, or code..."></div>
  <div class="col-6 col-md-2">
    <select name="status" class="form-select">
      <option value="">All status</option>
      <?php foreach (['Pending', 'Approved', 'Cancelled'] as $s): ?><option <?= $status === $s ? 'selected' : '' ?>><?= $s ?></option><?php endforeach; ?>
    </select></div>
  <div class="col-6 col-md-3"><input type="date" name="date" value="<?= e($date) ?>" class="form-control"></div>
  <div class="col-auto"><button class="btn btn-outline-primary">Filter</button> <a href="reservations.php" class="btn btn-outline-secondary">Reset</a></div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead><tr><th>Reserved by</th><th>Room</th><th>When</th><th>Purpose</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <?php if ($rows->num_rows === 0): ?><tr><td colspan="6" class="text-center text-muted py-4">No reservations found.</td></tr><?php endif; ?>
      <?php // one table row per reservation ?>
      <?php while ($r = $rows->fetch_assoc()): ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <span class="avatar"><?= e(initials($r['guest_name'])) ?></span>
              <div>
                <div class="fw-semibold"><?= e($r['guest_name']) ?> <?php if ($r['reference_code']): ?><span class="pill pill-gray ms-1"><?= e($r['reference_code']) ?></span><?php endif; ?></div>
                <div class="small text-muted"><?= e($r['contact']) ?></div>
                <?php if ($r['email']): ?><div class="small text-muted"><?= e($r['email']) ?></div><?php endif; ?>
              </div>
            </div>
          </td>
          <td class="text-nowrap"><?= e($r['room_name']) ?></td>
          <td class="text-nowrap"><?= date('M d, Y', strtotime($r['reservation_date'])) ?><div class="small text-muted"><?= fmt_time($r['start_time']) ?> - <?= fmt_time($r['end_time']) ?></div></td>
          <td><?= e($r['purpose']) ?><div class="small text-muted">Est. <?= cost_label($r['start_time'], $r['end_time'], $r['price_per_hour']) ?></div></td>
          <td><?= status_badge($r['status']) ?></td>
          <td class="text-end text-nowrap">
            <?php // the approve and reject buttons show only for pending ones ?>
            <?php if ($r['status'] === 'Pending'): ?>
            <form action="reservation_status.php" method="post" class="d-inline">
              <input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="status" value="Approved">
              <button class="btn btn-sm btn-approve">Approve</button>
            </form>
            <form action="reservation_status.php" method="post" class="d-inline" onsubmit="return confirm('Reject this request?');">
              <input type="hidden" name="id" value="<?= $r['id'] ?>"><input type="hidden" name="status" value="Cancelled">
              <button class="btn btn-sm btn-reject">Reject</button>
            </form>
            <?php endif; ?>
            <a href="reservation_form.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
            <?php // delete uses a POST form, confirm() asks first ?>
            <form action="reservation_delete.php" method="post" class="d-inline" onsubmit="return confirm('Delete this reservation?');">
              <input type="hidden" name="id" value="<?= $r['id'] ?>">
              <button class="btn btn-sm btn-outline-danger">Delete</button>
            </form>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
