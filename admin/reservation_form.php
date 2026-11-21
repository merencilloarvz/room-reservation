<?php
// ADMIN add/edit reservation (also used for walk-ins: the admin can book
// on behalf of a client and set the status straight to Approved)
require_once __DIR__ . '/../includes/auth.php';

// ?id= in the url = edit mode, no id = add mode
$id = (int)($_GET['id'] ?? 0);
// empty values for the add form
$res = ['room_id'=>'','guest_name'=>'','contact'=>'','email'=>'','purpose'=>'','reservation_date'=>'',
        'start_time'=>'','end_time'=>'','status'=>'Pending'];
// list of error messages
$errors = [];

// editing: get the saved reservation
if ($id) {
    $s = $conn->prepare("SELECT * FROM reservations WHERE id = ?");
    $s->bind_param("i", $id); $s->execute();
    $found = $s->get_result()->fetch_assoc();
    if (!$found) { flash('danger', 'Reservation not found.'); header('Location: reservations.php'); exit; }
    $res = $found;
}

// form submitted, get the values
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $res['room_id']          = (int)($_POST['room_id'] ?? 0);
    $res['guest_name']       = trim($_POST['guest_name'] ?? '');
    $res['contact']          = trim($_POST['contact'] ?? '');
    $res['email']            = trim($_POST['email'] ?? '');
    $res['purpose']          = trim($_POST['purpose'] ?? '');
    $res['reservation_date'] = $_POST['reservation_date'] ?? '';
    $res['start_time']       = $_POST['start_time'] ?? '';
    $res['end_time']         = $_POST['end_time'] ?? '';
    $res['status']           = $_POST['status'] ?? 'Pending';

    // check the inputs
    if (!$res['room_id'])            $errors[] = 'Please select a room.';
    if ($res['guest_name'] === '')   $errors[] = 'Name is required.';
    if ($res['contact'] === '')      $errors[] = 'Contact is required.';
    if ($res['purpose'] === '')      $errors[] = 'Purpose is required.';
    if ($res['email'] !== '' && !filter_var($res['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Email is not valid.';
    if ($res['reservation_date'] === '') $errors[] = 'Date is required.';
    elseif (!$id && $res['reservation_date'] < date('Y-m-d')) $errors[] = 'Date cannot be in the past.';
    if ($res['start_time'] === '' || $res['end_time'] === '') $errors[] = 'Start and end time are required.';
    elseif ($res['end_time'] <= $res['start_time']) $errors[] = 'End time must be later than start time.';
    // whitelist: the posted status must be one of these 3, anything else is rejected
    if (!in_array($res['status'], ['Pending','Approved','Cancelled'])) $errors[] = 'Invalid status.';

    // only inside the opening hours
    if (!$errors && ($res['start_time'] < OPEN_TIME || $res['end_time'] > CLOSE_TIME)) $errors[] = 'Reservations must be within opening hours (' . hours_label() . ').';

    // double-booking check. skipped when the booking is Cancelled,
    // a cancelled booking occupies nothing so it can't conflict
    if (!$errors && $res['status'] !== 'Cancelled') {
        // overlap rule: old start < new end AND old end > new start.
        // id <> ? = when editing, don't let the booking clash with itself
        $c = $conn->prepare("SELECT guest_name, start_time, end_time FROM reservations
            WHERE room_id = ? AND reservation_date = ? AND status <> 'Cancelled'
              AND start_time < ? AND end_time > ? AND id <> ? LIMIT 1");
        $c->bind_param("isssi", $res['room_id'], $res['reservation_date'], $res['end_time'], $res['start_time'], $id);
        $c->execute();
        // found an overlap = build a helpful error saying WHO already booked that time
        if ($clash = $c->get_result()->fetch_assoc()) {
            $errors[] = 'Time conflict: this room is already reserved by ' . $clash['guest_name'] . ' from '
                . date('g:i A', strtotime($clash['start_time'])) . ' to ' . date('g:i A', strtotime($clash['end_time'])) . '.';
        }
    }

    // no errors, save to the database
    if (!$errors) {
        if ($id) {
            // UPDATE when editing
            $s = $conn->prepare("UPDATE reservations SET room_id=?, guest_name=?, contact=?, email=?, purpose=?, reservation_date=?, start_time=?, end_time=?, status=? WHERE id=?");
            $s->bind_param("issssssssi", $res['room_id'], $res['guest_name'], $res['contact'], $res['email'], $res['purpose'], $res['reservation_date'], $res['start_time'], $res['end_time'], $res['status'], $id);
        } else {
            // INSERT when adding a new reservation
            $s = $conn->prepare("INSERT INTO reservations (reference_code, room_id, guest_name, contact, email, purpose, reservation_date, start_time, end_time, status) VALUES (?,?,?,?,?,?,?,?,?,?)");
            // new reservations get a reference code
            $ref = generate_reference($conn);
            $s->bind_param("sissssssss", $ref, $res['room_id'], $res['guest_name'], $res['contact'], $res['email'], $res['purpose'], $res['reservation_date'], $res['start_time'], $res['end_time'], $res['status']);
        }
        if ($s->execute()) {
            flash('success', $id ? 'Reservation updated.' : 'Reservation created.');
            header('Location: reservations.php'); exit;
        }
        $errors[] = 'Database error: ' . $conn->error;
    }
}

// room dropdown: Available rooms, PLUS the room already assigned when editing
// (otherwise editing a booking on a Maintenance room would lose its selection)
$rs = $conn->prepare("SELECT id, room_name, building, capacity FROM rooms WHERE status = 'Available' OR id = ? ORDER BY building, room_name");
$rs->bind_param("i", $res['room_id']); $rs->execute();
$rooms = $rs->get_result();

// header is loaded here because the redirects above must come before any output
require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1 class="page-title"><?= $id ? 'Edit reservation' : 'New reservation' ?></h1></div>
<?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<div class="card"><div class="card-body p-4">
<form method="post" class="row g-3">
  <div class="col-md-6"><label class="form-label">Room</label>
    <select name="room_id" class="form-select" required>
      <option value="">-- Select room --</option>
      <?php // one option per room ?>
      <?php while ($rm = $rooms->fetch_assoc()): ?>
        <option value="<?= $rm['id'] ?>" <?= $res['room_id']==$rm['id']?'selected':'' ?>><?= e($rm['room_name']) ?> (<?= e($rm['building']) ?>, cap. <?= $rm['capacity'] ?>)</option>
      <?php endwhile; ?>
    </select></div>
  <div class="col-md-6"><label class="form-label">Reserved By</label>
    <input type="text" name="guest_name" class="form-control" value="<?= e($res['guest_name']) ?>" required></div>
  <div class="col-md-6"><label class="form-label">Contact Number / Email</label>
    <input type="text" name="contact" class="form-control" value="<?= e($res['contact']) ?>" required></div>
  <div class="col-md-6"><label class="form-label">Email (optional)</label>
    <input type="email" name="email" maxlength="100" class="form-control" value="<?= e($res['email']) ?>"></div>
  <div class="col-12"><label class="form-label">Purpose</label>
    <input type="text" name="purpose" class="form-control" value="<?= e($res['purpose']) ?>" required></div>
  <div class="col-md-4"><label class="form-label">Date</label>
    <input type="date" name="reservation_date" class="form-control" value="<?= e($res['reservation_date']) ?>" required></div>
  <div class="col-6 col-md-3"><label class="form-label">Start Time</label>
    <?php // substr trims the seconds ("14:00:00" -> "14:00") so the time input accepts the value ?>
    <input type="time" min="<?= OPEN_TIME ?>" max="<?= CLOSE_TIME ?>" name="start_time" class="form-control" value="<?= e(substr($res['start_time'],0,5)) ?>" required></div>
  <div class="col-6 col-md-3"><label class="form-label">End Time</label>
    <input type="time" min="<?= OPEN_TIME ?>" max="<?= CLOSE_TIME ?>" name="end_time" class="form-control" value="<?= e(substr($res['end_time'],0,5)) ?>" required></div>
  <div class="col-md-2"><label class="form-label">Status</label>
    <select name="status" class="form-select">
      <?php foreach (['Pending','Approved','Cancelled'] as $st): ?><option <?= $res['status']===$st?'selected':'' ?>><?= $st ?></option><?php endforeach; ?>
    </select></div>
  <div class="col-12">
    <button class="btn btn-primary">Save</button>
    <a href="reservations.php" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
