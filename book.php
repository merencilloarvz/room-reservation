<?php
// PUBLIC booking form: clients fill this out to request a reservation.
// everything is saved as "Pending" first, the admin approves it after
require_once 'includes/public_init.php';

// the form values. empty at first, but room_id and date arrive pre-filled
// when the client clicked "Reserve this room" on index.php
$f = ['room_id' => (int)($_GET['room_id'] ?? 0), 'guest_name' => '', 'email' => '', 'contact' => '',
      'purpose' => '', 'reservation_date' => $_GET['date'] ?? date('Y-m-d'), 'start_time' => '', 'end_time' => ''];
// validation errors collected here, printed one line each above the form
$errors = [];

// form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // anti-bot trick ("honeypot"): the form has a hidden field named website.
    // humans never see it, bots fill in everything they find.
    // so if it arrives with text = almost certainly a bot, ignore it quietly
    if (!empty($_POST['website'])) { header('Location: index.php'); exit; }

    // get the values from the form
    $f['room_id']          = (int)($_POST['room_id'] ?? 0);
    $f['guest_name']       = trim($_POST['guest_name'] ?? '');
    $f['email']            = trim($_POST['email'] ?? '');
    $f['contact']          = trim($_POST['contact'] ?? '');
    $f['purpose']          = trim($_POST['purpose'] ?? '');
    $f['reservation_date'] = $_POST['reservation_date'] ?? '';
    $f['start_time']       = $_POST['start_time'] ?? '';
    $f['end_time']         = $_POST['end_time'] ?? '';

    // the room must really exist AND be Available.
    // we can't trust the posted room_id, anyone can change it in the browser devtools
    $rc = $conn->prepare("SELECT room_name FROM rooms WHERE id = ? AND status = 'Available'");
    $rc->bind_param("i", $f['room_id']); $rc->execute(); // "i" = room_id is an integer
    $room = $rc->get_result()->fetch_assoc();

    // validation, one error message per problem
    if (!$room)                       $errors[] = 'Please choose an available room.';
    if ($f['guest_name'] === '')      $errors[] = 'Your name is required.';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if ($f['contact'] === '')         $errors[] = 'Contact number is required.';
    if ($f['purpose'] === '')         $errors[] = 'Purpose is required.';
    if ($f['reservation_date'] === '' || $f['reservation_date'] < date('Y-m-d')) $errors[] = 'Please choose today or a future date.';
    if ($f['start_time'] === '' || $f['end_time'] === '') $errors[] = 'Start and end time are required.';
    elseif ($f['end_time'] <= $f['start_time']) $errors[] = 'End time must be later than start time.';
    // times must be inside the business hours (OPEN_TIME/CLOSE_TIME from functions.php)
    elseif ($f['start_time'] < OPEN_TIME || $f['end_time'] > CLOSE_TIME) $errors[] = 'We are open ' . hours_label() . '. Please pick a time inside those hours.';

    // double-booking check (only runs if all the checks above passed).
    // has_conflict() = is there a Pending/Approved booking already overlapping this time?
    if (!$errors && has_conflict($conn, $f['room_id'], $f['reservation_date'], $f['start_time'], $f['end_time'])) {
        $errors[] = 'Sorry, that room is already reserved during that time. Please pick another time or room.';
    }

    // everything passed -> save it as Pending (admin approves it later)
    if (!$errors) {
        // unique code like RR-3FA9C1, the client uses it on check_booking.php
        $ref = generate_reference($conn);
        $s = $conn->prepare("INSERT INTO reservations
            (reference_code, room_id, guest_name, email, contact, purpose, reservation_date, start_time, end_time, status)
            VALUES (?,?,?,?,?,?,?,?,?, 'Pending')");
        // "sisssssss" = the type of each ?: s=string, i=integer. 9 values for 9 ?
        $s->bind_param("sisssssss", $ref, $f['room_id'], $f['guest_name'], $f['email'], $f['contact'],
                       $f['purpose'], $f['reservation_date'], $f['start_time'], $f['end_time']);
        if ($s->execute()) {
            // placeholder, does nothing yet (see functions.php)
            send_confirmation_email($f['email'], $f['guest_name'], $ref, $room['room_name'],
                                    $f['reservation_date'], $f['start_time'], $f['end_time']);
            // send them to the success page with the code in the url
            // urlencode = make the code safe to put inside a link
            header('Location: booking_success.php?ref=' . urlencode($ref));
            exit; // important: stop the page right after a redirect
        }
        $errors[] = 'Something went wrong. Please try again.';
    }
}

// rooms for the dropdown
$rooms = $conn->query("SELECT id, room_name, building, capacity, price_per_hour FROM rooms WHERE status = 'Available' ORDER BY building, room_name");

// header printed last because the redirects above must happen before any html output
require_once 'includes/public_header.php';
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Book a room</h1>
    <p class="page-sub">Fill in the form. Your request will be reviewed and confirmed by the admin. We are open <?= hours_label() ?>.</p>
  </div>
</div>
<?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>

<div class="card"><div class="card-body p-4">
<form method="post" class="row g-3">
  <?php // honeypot anti-bot field (hidden with css). see the check at the top of this file ?>
  <div style="display:none"><input type="text" name="website" tabindex="-1" autocomplete="off"></div>

  <div class="col-md-6"><label class="form-label">Room</label>
    <select name="room_id" class="form-select" required>
      <option value="">-- Select room --</option>
      <?php // one option per available room ?>
      <?php while ($rm = $rooms->fetch_assoc()): ?>
        <option value="<?= $rm['id'] ?>" <?= $f['room_id'] == $rm['id'] ? 'selected' : '' ?>><?= e($rm['room_name']) ?> (<?= e($rm['building']) ?>, <?= people_label($rm['capacity']) ?><?= $rm['price_per_hour'] > 0 ? ', ' . money($rm['price_per_hour']) . '/hr' : '' ?>)</option>
      <?php endwhile; ?>
    </select></div>
  <div class="col-md-6"><label class="form-label">Your full name</label>
    <input type="text" name="guest_name" maxlength="100" class="form-control" value="<?= e($f['guest_name']) ?>" required></div>
  <div class="col-md-6"><label class="form-label">Email (Gmail)</label>
    <input type="email" name="email" maxlength="100" class="form-control" value="<?= e($f['email']) ?>" required></div>
  <div class="col-md-6"><label class="form-label">Contact number</label>
    <input type="text" name="contact" maxlength="50" class="form-control" value="<?= e($f['contact']) ?>" required></div>
  <div class="col-12"><label class="form-label">Purpose</label>
    <input type="text" name="purpose" maxlength="255" class="form-control" placeholder="e.g. Group study, Hackathon, Team meeting" value="<?= e($f['purpose']) ?>" required></div>
  <div class="col-md-4"><label class="form-label">Date</label>
    <input type="date" name="reservation_date" min="<?= date('Y-m-d') ?>" class="form-control" value="<?= e($f['reservation_date']) ?>" required></div>
  <div class="col-6 col-md-4"><label class="form-label">Start time</label>
    <input type="time" name="start_time" min="<?= OPEN_TIME ?>" max="<?= CLOSE_TIME ?>" class="form-control" value="<?= e($f['start_time']) ?>" required></div>
  <div class="col-6 col-md-4"><label class="form-label">End time</label>
    <input type="time" name="end_time" min="<?= OPEN_TIME ?>" max="<?= CLOSE_TIME ?>" class="form-control" value="<?= e($f['end_time']) ?>" required></div>
  <div class="col-12">
    <button class="btn btn-primary">Submit request</button>
    <a href="index.php" class="btn btn-outline-secondary">Back to rooms</a>
  </div>
</form>
</div></div>
<?php require_once 'includes/public_footer.php'; ?>
