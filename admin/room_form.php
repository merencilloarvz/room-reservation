<?php
// ADMIN add/edit room form. auth.php = only logged-in admins get here.
// one page does both: with ?id= in the url it loads that room and UPDATEs,
// without it shows an empty form and INSERTs a new room
require_once __DIR__ . '/../includes/auth.php';

// the ?id= from the url. 0 = add mode
$id = (int)($_GET['id'] ?? 0);
// default (empty) values for the add form
$room = ['room_name' => '', 'building' => '', 'room_type_id' => '', 'capacity' => '',
         'price_per_hour' => '0', 'status' => 'Available', 'description' => ''];
// list of error messages
$errors = [];

// editing: get the saved data of the room
if ($id) {
    $s = $conn->prepare("SELECT * FROM rooms WHERE id = ?");
    $s->bind_param("i", $id); $s->execute();
    $found = $s->get_result()->fetch_assoc();
    // room does not exist
    if (!$found) { flash('danger', 'Room not found.'); header('Location: rooms.php'); exit; }
    $room = $found;
}

// form submitted, get the values
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room['room_name']      = trim($_POST['room_name'] ?? '');
    $room['building']       = trim($_POST['building'] ?? '');
    $room['room_type_id']   = (int)($_POST['room_type_id'] ?? 0);
    $room['capacity']       = (int)($_POST['capacity'] ?? 0);
    $room['price_per_hour'] = (float)($_POST['price_per_hour'] ?? 0);
    $room['status']         = $_POST['status'] ?? 'Available';
    $room['description']    = trim($_POST['description'] ?? '');

    // check the inputs
    if ($room['room_name'] === '')    $errors[] = 'Room name is required.';
    if ($room['building'] === '')     $errors[] = 'Building / location is required.';
    if (!$room['room_type_id'])       $errors[] = 'Please choose a room type.';
    if ($room['capacity'] < 1)        $errors[] = 'Capacity must be at least 1.';
    if ($room['price_per_hour'] < 0)  $errors[] = 'Price cannot be negative.';
    if (!in_array($room['status'], ['Available', 'Maintenance'])) $errors[] = 'Invalid status.';

    // no errors, save to the database
    if (!$errors) {
        if ($id) {
            // edit mode = UPDATE the existing row
            $s = $conn->prepare("UPDATE rooms SET room_name=?, building=?, room_type_id=?, capacity=?, price_per_hour=?, status=?, description=? WHERE id=?");
            // "ssiidssi" = type of each value in order: s=string, i=int, d=decimal
            $s->bind_param("ssiidssi", $room['room_name'], $room['building'], $room['room_type_id'], $room['capacity'], $room['price_per_hour'], $room['status'], $room['description'], $id);
        } else {
            // add mode = INSERT a new row
            $s = $conn->prepare("INSERT INTO rooms (room_name, building, room_type_id, capacity, price_per_hour, status, description) VALUES (?,?,?,?,?,?,?)");
            // "ssiidss" = one letter per ?, same order as the columns
            $s->bind_param("ssiidss", $room['room_name'], $room['building'], $room['room_type_id'], $room['capacity'], $room['price_per_hour'], $room['status'], $room['description']);
        }
        // run the query, go back to the list if it worked
        if ($s->execute()) {
            flash('success', $id ? 'Room updated.' : 'Room added.');
            header('Location: rooms.php'); exit;
        }
        $errors[] = 'Database error: ' . $conn->error;
    }
}

// the room types for the dropdown (the admin manages them in Room Types)
$types = $conn->query("SELECT id, type_name FROM room_types ORDER BY type_name");

// header is loaded here because the redirects above must come before any output
require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-head"><h1 class="page-title"><?= $id ? 'Edit room' : 'Add room' ?></h1></div>
<?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>
<?php if ($types->num_rows === 0): ?>
  <div class="alert alert-warning">There are no room types yet. <a href="room_types.php">Add a room type first</a>.</div>
<?php endif; ?>
<div class="card"><div class="card-body p-4">
<form method="post" class="row g-3">
  <div class="col-md-6"><label class="form-label">Room name</label>
    <input type="text" name="room_name" maxlength="100" class="form-control" placeholder="e.g. Quiet Study Room" value="<?= e($room['room_name']) ?>" required></div>
  <div class="col-md-6"><label class="form-label">Building / location</label>
    <input type="text" name="building" maxlength="100" class="form-control" placeholder="e.g. 2nd Floor" value="<?= e($room['building']) ?>" required></div>
  <div class="col-md-3"><label class="form-label">Room type</label>
    <select name="room_type_id" class="form-select" required>
      <option value="">-- Select --</option>
      <?php // one option per room type, selected keeps the old value when editing ?>
      <?php while ($t = $types->fetch_assoc()): ?>
        <option value="<?= $t['id'] ?>" <?= $room['room_type_id'] == $t['id'] ? 'selected' : '' ?>><?= e($t['type_name']) ?></option>
      <?php endwhile; ?>
    </select></div>
  <div class="col-md-3"><label class="form-label">Capacity (people)</label>
    <input type="number" min="1" name="capacity" class="form-control" value="<?= e($room['capacity']) ?>" required></div>
  <div class="col-md-3"><label class="form-label">Price per hour (&#8369;)</label>
    <input type="number" min="0" step="0.01" name="price_per_hour" class="form-control" value="<?= e($room['price_per_hour']) ?>" required></div>
  <div class="col-md-3"><label class="form-label">Status</label>
    <select name="status" class="form-select">
      <?php foreach (['Available', 'Maintenance'] as $st): ?><option <?= $room['status'] === $st ? 'selected' : '' ?>><?= $st ?></option><?php endforeach; ?>
    </select></div>
  <div class="col-12"><label class="form-label">Description</label>
    <textarea name="description" rows="3" class="form-control" placeholder="e.g. Power outlets, Wi-Fi, whiteboard"><?= e($room['description']) ?></textarea></div>
  <div class="col-12">
    <button class="btn btn-primary">Save</button>
    <a href="rooms.php" class="btn btn-outline-secondary">Cancel</a>
  </div>
</form>
</div></div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
