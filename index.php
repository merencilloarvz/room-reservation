<?php
// PUBLIC home page: shows every room + its availability for a chosen date.
// no login needed, clients can view and book straight from here
require_once 'includes/public_init.php';

// the date being viewed (from the url), default = today.
// the ?? means: if ?date= isn't in the url, use today instead
$date = $_GET['date'] ?? date('Y-m-d');
// check the format (YYYY-MM-DD) and block past dates. anything weird = fall back to today
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || $date < date('Y-m-d')) $date = date('Y-m-d');
// the small label above each bar says Today or the date
$day_label = ($date === date('Y-m-d')) ? 'Today' : date('D, M j', strtotime($date));

// all rooms + their type name. JOIN needed because the type name lives in another table
$rooms = $conn->query("SELECT r.*, t.type_name FROM rooms r JOIN room_types t ON t.id = r.room_type_id ORDER BY r.status, r.room_name");

// that day's bookings (cancelled ones don't block a slot), grouped by room id.
// prepared statement: the ? gets filled by bind_param, so the date can't be
// used for sql injection. this is a PUBLIC page so we only take times/status,
// no guest names (privacy)
$booked = [];
$s = $conn->prepare("SELECT room_id, start_time, end_time, status FROM reservations
                     WHERE reservation_date = ? AND status <> 'Cancelled' ORDER BY start_time");
$s->bind_param("s", $date);
$s->execute();
$res = $s->get_result();
// $booked[room id] = list of that room's time slots
while ($b = $res->fetch_assoc()) { $booked[$b['room_id']][] = $b; }

// header printed after the queries on purpose: header() redirects
// must happen BEFORE any html output
require_once 'includes/public_header.php';
?>
<div class="hero">
  <div>
    <h2>Find and reserve a room</h2>
    <p>No account needed. Pick a date, check availability, then send a reservation request.</p>
    <span class="hero-note"><i class="bi bi-clock"></i> Open daily, <?= hours_label() ?></span>
  </div>
  <form method="get">
    <label for="date">Choose a date</label>
    <input type="date" id="date" name="date" value="<?= e($date) ?>" min="<?= date('Y-m-d') ?>" class="form-control" onchange="this.form.submit()">
  </form>
</div>

<div class="small text-muted mb-3">
  Showing availability for <strong class="text-dark"><?= date('l, F j, Y', strtotime($date)) ?></strong>
  <span class="legend-dot" style="background:var(--red)"></span>Booked
  <span class="legend-dot" style="background:#b7791f"></span>Pending
</div>

<div class="row g-3">
<?php // loop: one card per room. $slots = this room's bookings for the day (empty array = free) ?>
<?php while ($r = $rooms->fetch_assoc()): $slots = $booked[$r['id']] ?? []; $off = ($r['status'] === 'Maintenance'); ?>
  <div class="col-md-6 col-lg-4">
    <div class="card room-card h-100 <?= $off ? 'st-off' : ($slots ? 'st-part' : 'st-free') ?>">
      <div class="card-body d-flex flex-column">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
          <h5 class="room-name"><?= e($r['room_name']) ?></h5>
          <?php // badge shows unavailable, free all day or partly booked ?>
          <?php if ($off): ?><span class="pill pill-gray">Unavailable</span>
          <?php elseif (!$slots): ?><span class="pill pill-green">Free all day</span>
          <?php else: ?><span class="pill pill-amber">Partly booked</span><?php endif; ?>
        </div>
        <div class="room-meta">
          <span><i class="bi bi-geo-alt"></i> <?= e($r['building']) ?></span>
          <span><i class="bi bi-people"></i> <?= people_label($r['capacity']) ?></span>
          <span><i class="bi bi-tag"></i> <?= e($r['type_name']) ?></span>
        </div>
        <p class="small text-muted mt-2 mb-1"><?= e($r['description']) ?></p>
        <div class="room-price mb-3"><?= $r['price_per_hour'] > 0 ? money($r['price_per_hour']) . ' <small>/ hour</small>' : 'Free' ?></div>

        <div class="slot-label"><?= e($day_label) ?>, <?= hours_label() ?></div>
        <div class="timeline timeline-sm">
          <?php // red = approved, yellow = pending ?>
          <?php foreach ($slots as $b): ?>
            <div class="slot slot-<?= strtolower($b['status']) ?>" style="<?= slot_style($b['start_time'], $b['end_time']) ?>"></div>
          <?php endforeach; ?>
        </div>
        <div class="mb-3">
          <?php if ($off): ?><span class="small text-muted">Closed for repairs</span>
          <?php elseif (!$slots): ?><span class="small text-success">No bookings <?= $day_label === 'Today' ? 'today' : 'on this day' ?></span><?php endif; ?>
          <?php foreach ($slots as $b): ?>
            <span class="chip <?= $b['status'] === 'Approved' ? 'chip-red' : 'chip-amber' ?>"><?= fmt_time($b['start_time']) ?> - <?= fmt_time($b['end_time']) ?></span>
          <?php endforeach; ?>
        </div>

        <div class="mt-auto">
          <?php // only available rooms can be reserved ?>
          <?php if (!$off): ?>
            <a href="book.php?room_id=<?= $r['id'] ?>&date=<?= e($date) ?>" class="btn btn-primary w-100">Reserve this room</a>
          <?php else: ?>
            <button class="btn btn-muted w-100" disabled>Not available</button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
<?php endwhile; ?>
</div>
<?php require_once 'includes/public_footer.php'; ?>
