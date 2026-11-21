<?php
// ADMIN room types page (the categories rooms belong to, ex. Study Room).
// this single page handles add + edit + delete
require_once __DIR__ . '/../includes/auth.php';

$errors = [];
// ?edit=5 in the url = load that type into the form, no ?edit = add mode
$edit_id = (int)($_GET['edit'] ?? 0);
$type = ['type_name' => '', 'description' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    // DELETE action. the hidden "action" input tells us if the form was save or delete.
    // same guard as room_delete.php: a type still used by rooms cannot be deleted
    if ($action === 'delete') {
        $c = $conn->prepare("SELECT COUNT(*) c FROM rooms WHERE room_type_id = ?");
        $c->bind_param("i", $id); $c->execute();
        if ($c->get_result()->fetch_assoc()['c'] > 0) {
            flash('warning', 'Cannot delete: some rooms still use this type.');
        } else {
            $d = $conn->prepare("DELETE FROM room_types WHERE id = ?");
            $d->bind_param("i", $id); $d->execute();
            flash('success', 'Room type deleted.');
        }
        header('Location: room_types.php'); exit;
    }

    // save action: INSERT for a new type, UPDATE when editing
    if ($action === 'save') {
        $type['type_name']   = trim($_POST['type_name'] ?? '');
        $type['description'] = trim($_POST['description'] ?? '');
        $edit_id = $id;

        if ($type['type_name'] === '') {
            $errors[] = 'Type name is required.';
        } else {
            // two types cannot have the same name (id <> ? skips the one we are editing)
            $d = $conn->prepare("SELECT id FROM room_types WHERE type_name = ? AND id <> ?");
            $d->bind_param("si", $type['type_name'], $id); $d->execute();
            if ($d->get_result()->num_rows > 0) $errors[] = 'That room type already exists.';
        }

        if (!$errors) {
            if ($id) {
                $s = $conn->prepare("UPDATE room_types SET type_name = ?, description = ? WHERE id = ?");
                $s->bind_param("ssi", $type['type_name'], $type['description'], $id);
            } else {
                $s = $conn->prepare("INSERT INTO room_types (type_name, description) VALUES (?, ?)");
                $s->bind_param("ss", $type['type_name'], $type['description']);
            }
            $s->execute();
            flash('success', $id ? 'Room type updated.' : 'Room type added.');
            header('Location: room_types.php'); exit;
        }
    }
} elseif ($edit_id) {
    // editing: get the saved type
    $s = $conn->prepare("SELECT type_name, description FROM room_types WHERE id = ?");
    $s->bind_param("i", $edit_id); $s->execute();
    $found = $s->get_result()->fetch_assoc();
    if ($found) { $type = $found; } else { $edit_id = 0; }
}

// the type list + how many rooms use each one.
// LEFT JOIN = types with 0 rooms still show up (with a count of 0)
$list = $conn->query("SELECT t.*, COUNT(r.id) AS room_count FROM room_types t
    LEFT JOIN rooms r ON r.room_type_id = t.id GROUP BY t.id ORDER BY t.type_name");

// header is loaded here because the redirects above must come before any output
require_once __DIR__ . '/../includes/header.php';
?>
<div class="page-head">
  <div>
    <h1 class="page-title">Room types</h1>
    <p class="page-sub">Create the kinds of spaces your business rents out, like Study Room or Hackathon Hall.</p>
  </div>
</div>
<?php foreach ($errors as $er): ?><div class="alert alert-danger py-2"><?= e($er) ?></div><?php endforeach; ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card"><div class="card-header"><?= $edit_id ? 'Edit room type' : 'Add room type' ?></div>
    <div class="card-body">
      <form method="post">
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= $edit_id ?>">
        <div class="mb-3"><label class="form-label">Type name</label>
          <input type="text" name="type_name" maxlength="50" class="form-control" value="<?= e($type['type_name']) ?>" required></div>
        <div class="mb-3"><label class="form-label">Description (optional)</label>
          <input type="text" name="description" maxlength="150" class="form-control" value="<?= e($type['description']) ?>"></div>
        <button class="btn btn-primary">Save</button>
        <?php if ($edit_id): ?><a href="room_types.php" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
      </form>
    </div></div>
  </div>
  <div class="col-lg-8">
    <div class="card">
      <div class="table-responsive">
        <table class="table">
          <thead><tr><th>Type</th><th>Rooms</th><th class="text-end">Actions</th></tr></thead>
          <tbody>
          <?php if ($list->num_rows === 0): ?><tr><td colspan="3" class="text-center text-muted py-4">No room types yet.</td></tr><?php endif; ?>
          <?php // one table row per type ?>
          <?php while ($t = $list->fetch_assoc()): ?>
            <tr>
              <td><div class="fw-semibold"><?= e($t['type_name']) ?></div><div class="small text-muted"><?= e($t['description']) ?></div></td>
              <td><?= (int)$t['room_count'] ?></td>
              <td class="text-end text-nowrap">
                <a href="room_types.php?edit=<?= $t['id'] ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                <form method="post" class="d-inline" onsubmit="return confirm('Delete this room type?');">
                  <input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= $t['id'] ?>">
                  <button class="btn btn-sm btn-outline-danger">Delete</button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
