<?php
// ADMIN room list with search
require_once __DIR__ . '/../includes/header.php';

// search word from the search box (?q=), empty = show everything
$q = trim($_GET['q'] ?? '');
// wrap the word in % wildcards so it matches anything CONTAINING it,
// ex. "study" matches "Quiet Study Room"
$like = "%$q%";
// rooms that match the search. JOIN = also grab the type name from room_types
$stmt = $conn->prepare("SELECT r.*, t.type_name FROM rooms r
    JOIN room_types t ON t.id = r.room_type_id
    WHERE r.room_name LIKE ? OR r.building LIKE ? OR t.type_name LIKE ?
    ORDER BY r.building, r.room_name");
$stmt->bind_param("sss", $like, $like, $like);
$stmt->execute();
$rooms = $stmt->get_result();
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Rooms</h1>
    <p class="page-sub">Manage the rooms, tables and spaces clients can reserve.</p>
  </div>
  <a href="room_form.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Add room</a>
</div>

<form class="row g-2 filter-bar mx-0 align-items-center" method="get">
  <div class="col-sm-8 col-md-5"><input type="text" name="q" value="<?= e($q) ?>" class="form-control" placeholder="Search room, building, or type..."></div>
  <div class="col-auto"><button class="btn btn-outline-primary">Search</button> <a href="rooms.php" class="btn btn-outline-secondary">Reset</a></div>
</form>

<div class="card">
  <div class="table-responsive">
    <table class="table">
      <thead><tr><th>Room</th><th>Type</th><th>Capacity</th><th>Price</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
      <tbody>
      <?php if ($rooms->num_rows === 0): ?><tr><td colspan="6" class="text-center text-muted py-4">No rooms found.</td></tr><?php endif; ?>
      <?php // one table row per room ?>
      <?php while ($r = $rooms->fetch_assoc()): ?>
        <tr>
          <td><div class="fw-semibold"><?= e($r['room_name']) ?></div><div class="small text-muted"><?= e($r['building']) ?></div></td>
          <td><?= e($r['type_name']) ?></td>
          <td><?= people_label($r['capacity']) ?></td>
          <td><?= $r['price_per_hour'] > 0 ? money($r['price_per_hour']) . ' / hr' : 'Free' ?></td>
          <td><?= status_badge($r['status']) ?></td>
          <td class="text-end text-nowrap">
            <a href="room_form.php?id=<?= $r['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
            <?php // delete uses a POST form, confirm() asks first ?>
            <form action="room_delete.php" method="post" class="d-inline" onsubmit="return confirm('Delete this room?');">
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
