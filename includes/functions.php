<?php
// all the helper functions shared by the admin and public pages.
// most are small formatting helpers. the important ones:
// has_conflict() = the double-booking check, generate_reference() = booking codes

// business hours. bookings outside this range get rejected.
// change these 2 lines if the store schedule changes
define('OPEN_TIME', '08:00');
define('CLOSE_TIME', '20:00');

// escape text before printing it into html. without this, someone could type
// <script> into a form field and it would actually run on the page (xss attack)
function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } // ENT_QUOTES = quotes get escaped too

// saves a one-time message (ex. "Room added.") in the session.
// the NEXT page prints it once then deletes it - that's the green success banner
function flash($type, $msg) { $_SESSION['flash'] = ['type' => $type, 'msg' => $msg]; }

// small colored pill for a status (green approved, amber pending, red maintenance...)
// the ?? 'gray' part = fallback color if the status isn't in the list
function status_badge($s) {
    $c = ['Pending' => 'amber', 'Approved' => 'green', 'Cancelled' => 'gray',
          'Available' => 'green', 'Maintenance' => 'red'];
    return '<span class="pill pill-' . ($c[$s] ?? 'gray') . '">' . e($s) . '</span>';
}

// turns 14:30:00 into 2:30 PM
function fmt_time($t) { return date('g:i A', strtotime($t)); }

// shows the opening hours like 8 AM - 8 PM
function hours_label() { return date('g A', strtotime(OPEN_TIME)) . ' - ' . date('g A', strtotime(CLOSE_TIME)); }

// 1 person, 12 people
function people_label($n) { return (int)$n . ((int)$n === 1 ? ' person' : ' people'); }

// shows a price like ₱50. the two rtrims strip the unneeded ".00" from "50.00"
function money($n) { return '₱' . rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); }

// first letters of the name for the round avatar (Maria Santos = MS)
function initials($name) {
    $out = '';
    foreach (array_slice(preg_split('/\s+/', trim($name)), 0, 2) as $w) {
        if (preg_match('/./u', $w, $m)) $out .= strtoupper($m[0]);
    }
    return $out ?: '?';
}

// how many hours between two times
function duration_hours($start, $end) { return (strtotime($end) - strtotime($start)) / 3600; }

// price x hours, used for the estimated total
function total_cost($start, $end, $price_per_hour) { return duration_hours($start, $end) * $price_per_hour; }

// estimated total as text, or Free when the room has no price
function cost_label($start, $end, $price_per_hour) {
    return $price_per_hour > 0 ? money(total_cost($start, $end, $price_per_hour)) : 'Free';
}

// timeline math, used by index.php and dashboard.php.
// every booking becomes a small bar with a left position + width in percent.
// the whole timeline = the business hours, so a noon booking sits in the middle.
// max/min "clamp" the times so a booking outside business hours can't overflow the bar
function slot_style($start, $end) {
    $open = strtotime(OPEN_TIME);
    $close = strtotime(CLOSE_TIME);
    $s = max(strtotime($start), $open);
    $e = min(strtotime($end), $close);
    $left = ($s - $open) / ($close - $open) * 100;
    $width = max(($e - $s) / ($close - $open) * 100, 0);
    return 'left:' . round($left, 2) . '%;width:' . round($width, 2) . '%';
}

// hour labels above the timeline (every 2 hours) with their position in percent
function axis_labels() {
    $open = (int)substr(OPEN_TIME, 0, 2);
    $close = (int)substr(CLOSE_TIME, 0, 2);
    $out = [];
    for ($h = $open; $h < $close; $h += 2) {
        $out[] = [round(($h - $open) / ($close - $open) * 100, 2), date('g A', mktime($h, 0, 0))];
    }
    return $out;
}

// THE double-booking check (book.php and reservation_form.php call this).
// two bookings overlap when: existing start < new end AND existing end > new start
// (easier to understand if you draw two bars on a line)
// status <> 'Cancelled' = cancelled bookings don't occupy the slot anymore.
// $exclude_id is passed when EDITING so a booking doesn't clash with itself
function has_conflict($conn, $room_id, $date, $start, $end, $exclude_id = 0) {
    $s = $conn->prepare("SELECT id FROM reservations
        WHERE room_id = ? AND reservation_date = ? AND status <> 'Cancelled'
          AND start_time < ? AND end_time > ? AND id <> ? LIMIT 1");
    $s->bind_param("isssi", $room_id, $date, $end, $start, $exclude_id); // types in order: int,string,string,string,int
    $s->execute();
    return $s->get_result()->num_rows > 0;
}

// booking code like RR-3FA9C1 that clients use on check_booking.php.
// random_bytes = secure random, substr = take only 6 hex characters.
// the do-while keeps generating if the code already exists (very unlikely, but safe)
function generate_reference($conn) {
    do {
        $code = 'RR-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
        $s = $conn->prepare("SELECT id FROM reservations WHERE reference_code = ?");
        $s->bind_param("s", $code);
        $s->execute();
    } while ($s->get_result()->num_rows > 0);
    return $code;
}

// email sending is not working yet. book.php already calls this, so when i get
// mail() or PHPMailer working i only need to fill this one function in.
// heads up: free hosting usually blocks mail().
// returning false just means "no email sent", the page still works fine
function send_confirmation_email($to, $name, $reference, $room, $date, $start, $end) {
    // example for later:
    // mail($to, "Reservation $reference", "Hi $name, your request is pending.");
    return false;
}
