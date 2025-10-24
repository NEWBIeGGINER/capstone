<?php 
require_once 'components/connect.php';
require_once 'components/auth.php';

// --- Initialize arrays ---
$events = [];
$holidays_for_list = [];
$event_announcements = [];

// === 🗓️ Appointment Slot Availability ===
try {
    $stmt = $conn->query("SELECT slots_date, SUM(slots) AS total_slots FROM schedules GROUP BY slots_date");
    $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dates as $row) {
        $date = $row['slots_date'];
        $totalSlots = (int)$row['total_slots'];

        // Skip invalid or empty dates
        if (!$date) continue;

        // Count booked appointments
        $stmt2 = $conn->prepare("
            SELECT COUNT(*) 
            FROM appointments 
            WHERE appointment_date = ? 
              AND status IN ('Pending','Approved')
        ");
        $stmt2->execute([$date]);
        $bookedCount = (int)$stmt2->fetchColumn();

        $availableSlots = max($totalSlots - $bookedCount, 0);

        // Define event color and title based on availability
        if ($availableSlots <= 0) {
            $color = '#e74c3c'; // red
            $titleText = "Fully Booked";
        } else {
            $color = '#2ecc71'; // green
            $titleText = "Available";
        }

        $events[] = [
            'title' => $titleText,
            'start' => $date,
            'color' => $color,
            'allDay' => true,
            'extendedProps' => [
                'slots' => $availableSlots,
                'isHoliday' => false,
                'isEvent' => false
            ]
        ];
    }
} catch (Exception $e) {
    error_log("Schedule load failed: " . $e->getMessage());
}

// === 🎉 Holidays ===
try {
    $holidays = $conn->query("SELECT * FROM holidays")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($holidays as $h) {
        if (empty($h['holiday_date'])) continue;

        // Handle "MM-DD" format or full date format
        $holidayDate = preg_match('/^\d{1,2}-\d{1,2}$/', $h['holiday_date'])
            ? date('Y-m-d', strtotime(date('Y') . '-' . $h['holiday_date']))
            : date('Y-m-d', strtotime($h['holiday_date']));
        
        $desc = htmlspecialchars($h['description'] ?: 'Holiday', ENT_QUOTES, 'UTF-8');

        $events[] = [
            'title' => $desc,
            'start' => $holidayDate,
            'color' => '#9b59b6', // purple
            'allDay' => true,
            'extendedProps' => [
                'isHoliday' => true,
                'slots' => 0,
                'isEvent' => false
            ]
        ];

        $holidays_for_list[] = ['date' => $holidayDate, 'description' => $desc];
    }
} catch (Exception $e) {
    error_log("Holiday fetch failed: " . $e->getMessage());
}

// === 📢 Community / Events Announcements ===
try {
    $query_events = $conn->query("
        SELECT id, title, event_date, short_description 
        FROM events 
        ORDER BY event_date ASC 
        LIMIT 3
    ");
    $event_announcements = $query_events->fetchAll(PDO::FETCH_ASSOC);

    foreach ($event_announcements as $ev) {
        if (empty($ev['event_date'])) continue;

        $events[] = [
            'title' => htmlspecialchars($ev['title'], ENT_QUOTES, 'UTF-8'),
            'start' => $ev['event_date'],
            'color' => '#f39c12', // orange
            'allDay' => true,
            'extendedProps' => [
                'isHoliday' => false,
                'slots' => 0,
                'isEvent' => true
            ]
        ];
    }
} catch (Exception $e) {
    error_log("Events fetch failed: " . $e->getMessage());
}

// === 🐶 Active Services ===
try {
    $services = $conn->query("SELECT * FROM services WHERE status='active'")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $services = [];
    error_log("Services fetch failed: " . $e->getMessage());
}
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Petcare | Appointment</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/appointments.css">
    <link rel="stylesheet" href="assets/css/header.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>

<body>
<?php include 'components/user_header.php'; ?>

<div class="container">
    <div class="calendar-section">
        <h2>CHOOSE DATE FOR APPOINTMENT</h2>
        <div id="calendar"></div>
    </div>

    <div class="info-section">
        <div class="legend">
            <h3>LEGEND</h3>
            <div class="legend-item"><div class="color-box available-color"></div>Available</div>
            <div class="legend-item"><div class="color-box no-slots-color"></div>No Slots</div>
            <div class="legend-item"><div class="color-box fullybooked-color"></div>Fully Book</div>
            <div class="legend-item"><div class="color-box holiday-color"></div>Holidays</div>
        </div>

        <div class="holidays-list">
            <h3>HOLIDAYS</h3>
            <ul>
                <?php foreach ($holidays_for_list as $h): ?>
                    <li><?= $h['date'] ?> - <?= htmlspecialchars($h['description']) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="events-list">
            <h3>ANNOUNCEMENTS</h3>
            <ul>
                <?php if (!empty($event_announcements)): ?>
                    <?php foreach ($event_announcements as $ev): ?>
                        <li>
                            <strong><?= htmlspecialchars($ev['title']) ?></strong><br>
                            <small><?= date("F j, Y", strtotime($ev['event_date'])) ?></small><br>
                            <em><?= htmlspecialchars($ev['short_description']) ?></em>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li>No upcoming events</li>
                <?php endif; ?>
            </ul>
            <a href="community.php" class="see-more-btn">See more →</a>
        </div>
    </div>
</div>

<?php include 'components/appointment_modal.php'; ?>

<script>
// Set up global variables BEFORE loading appointment.js
window.calendarEvents = <?= json_encode($events, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

window.servicesList = <?= json_encode(array_map(function($s){
    return [
        'id' => $s['id'],
        'name' => $s['name'],
        'img' => 'uploaded_files/' . $s['image']
    ];
}, $services), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>;

// Set user ID - ensure it's a string or empty string
window.currentUserId = "<?php echo $_SESSION['user_id'] ?? ''; ?>";

// Set API URL
window.FETCH_APPOINTMENT_URL = "components/fetch_appointments.php";

// Log for debugging
console.log('✓ Calendar events loaded:', window.calendarEvents?.length);
console.log('✓ Services loaded:', window.servicesList?.length);
console.log('✓ Current user ID:', window.currentUserId || '(not logged in)');
console.log('✓ API URL:', window.FETCH_APPOINTMENT_URL);
</script>


<script src="assets/js/appointment.js"></script>
<script src="assets/js/script.js"></script>



<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
</body>
</html>
