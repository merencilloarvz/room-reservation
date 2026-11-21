# Reserve — Study Hub Room Reservation System

A lightweight **PHP + MySQL + Bootstrap 5** web application for a study hub / co-working space.
Clients browse rooms, see live availability on a timeline, and send a booking request **without an account**.
The admin logs in to approve or reject requests, manage rooms, room types, prices, and view who booked every room.



---

Features

# Public site (no login needed)
- Room gallery (`index.php`) — all rooms with building, capacity, type, price, and a per-day availability timeline (red = approved, amber = pending). Pick any date to see that day.
- Booking form (`book.php`) — reserve a room with name, email, contact, purpose, date and time. Includes validation, opening-hours check, double-booking check, and a hidden anti-bot field.
- Reference code — every booking gets a unique code like `RR-3FA9C1` shown on a success page.
- Check booking (`check_booking.php`) — a client looks up their booking status with their reference code **and** email (both must match).

# Admin panel (login required, in the `admin/` folder)
- Dashboard (`admin/dashboard.php`) — stat cards (total rooms, available, reservations today, pending), a pending-requests inbox with one-click Approve / Reject, and a per-room availability timeline showing **who** booked each slot.
- Rooms (`admin/rooms.php`, `admin/room_form.php`) — add / edit / delete rooms (name, building, type, capacity, price per hour, status, description) with search. Rooms with existing reservations cannot be deleted (set them to *Maintenance* instead).
- Room types (`admin/room_types.php`) — categories like *Study Room*, *Meeting Room*, *Hackathon Hall*, *Co-working Table*. A type still used by rooms cannot be deleted; duplicate names are blocked.
- Reservations (`admin/reservations.php`, `admin/reservation_form.php`) — searchable, filterable list (by text, status, date); approve / reject / edit / delete; the admin can also create bookings on behalf of walk-in clients.

---

## 🛠 Tech stack

| Layer     | Technology                                        |
|-----------|---------------------------------------------------|
| Backend   | PHP 8 (mysqli, prepared statements everywhere)    |
| Database  | MySQL / MariaDB (InnoDB, foreign keys)            |
| Frontend  | Bootstrap 5.3, Bootstrap Icons, Inter font        |
| Styling   | Custom black & white theme in `assets/style.css`  |
| Server    | XAMPP (local) / Apache + PHP hosting (InfinityFree) |

---

## 📁 Project structure

```
room_reservation/
├── index.php               Public: room gallery + availability timelines
├── book.php                Public: booking request form
├── booking_success.php     Public: confirmation page with reference code
├── check_booking.php       Public: look up a booking by code + email
├── login.php               Admin login (password_verify, session hardening)
├── logout.php              Destroys the session
│
├── admin/                  Admin panel pages (login required)
│   ├── dashboard.php           Stats, pending requests, room timelines
│   ├── rooms.php               Room list + search
│   ├── room_form.php           Add / edit a room
│   ├── room_delete.php         Delete a room (guarded)
│   ├── room_types.php          Manage room categories
│   ├── reservations.php        Reservation list + filters
│   ├── reservation_form.php    Add / edit a reservation
│   ├── reservation_status.php  Approve / cancel a reservation
│   └── reservation_delete.php  Delete a reservation
│
├── includes/               Shared PHP building blocks
│   ├── db_connect.php      MySQL connection + timezone settings
│   ├── functions.php       Helpers: escaping, flash messages, badges,
│   │                       timeline math, conflict check, reference codes
│   ├── auth.php            Login guard (admin pages)
│   ├── public_init.php     Session + DB + helpers (public pages)
│   ├── header.php          Admin navbar (uses auth.php)
│   ├── footer.php          Admin footer
│   ├── public_header.php   Public navbar + HTML head
│   └── public_footer.php   Public footer + Bootstrap JS
│
├── assets/
│   └── style.css           Custom theme (overrides Bootstrap)
│
└── database/
    ├── database.sql        Full schema + sample data (fresh install) — KEEP
    ├── migration.sql       Old upgrade script — safe to delete
    ├── migration2.sql      Old upgrade script — safe to delete
    └── room_reservation.sql  phpMyAdmin backup dump (optional)
```

Public pages stay at the root so the URLs stay short. Admin pages live in `admin/`,
shared code in `includes/`, styling in `assets/`, SQL files in `database/`.

---



# How a booking flows

```
Client picks a room on index.php
        │
        ▼
book.php  →  validated + conflict-checked  →  saved as PENDING
        │                                      with reference code RR-XXXXXX
        ▼
booking_success.php shows the code
        │
        ▼
Admin sees it on admin/dashboard.php / admin/reservations.php
        │
   Approve ──► status = APPROVED (slot turns red/green everywhere)
   Reject  ──► status = CANCELLED (time slot is released)
        │
        ▼
Client checks status anytime via check_booking.php (code + email)


---

## 📄 License

Educational / portfolio project — free to use and modify.
