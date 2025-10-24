<?php
// Enable error reporting during development, disable in production
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

require_once 'connect.php';
require_once 'auth.php';
header('Content-Type: application/json; charset=utf-8');

try {
    $action = $_GET['action'] ?? '';

    if ($action === 'fetch') {
        // --- Fetch calendar events (month view) ---
        $events = [];

        // --- Holidays ---
        $holidays = $conn->query("SELECT * FROM holidays")->fetchAll(PDO::FETCH_ASSOC);
        $holiday_dates = [];
        foreach ($holidays as $h) {
            $holidayDate = preg_match('/^\d{1,2}-\d{1,2}$/', $h['holiday_date'])
                ? date('Y-m-d', strtotime(date('Y') . '-' . $h['holiday_date']))
                : date('Y-m-d', strtotime($h['holiday_date']));

            $events[] = [
                'title' => htmlspecialchars($h['description'] ?: 'Holiday'),
                'start' => $holidayDate,
                'color' => '#9b59b6',
                'allDay' => true,
                'extendedProps' => ['isHoliday' => true, 'slots' => 0]
            ];
            $holiday_dates[] = $holidayDate;
        }

        // --- Schedules grouped by date ---
        $stmt = $conn->query("SELECT slots_date FROM schedules GROUP BY slots_date");
        $dates = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($dates as $row) {
            $date = $row['slots_date'];
            if (in_array($date, $holiday_dates)) continue; // skip holidays

            $stmt2 = $conn->prepare("SELECT * FROM schedules WHERE slots_date=? AND status='available'");
            $stmt2->execute([$date]);
            $schedules = $stmt2->fetchAll(PDO::FETCH_ASSOC);

            $availableSlots = 0;
            foreach ($schedules as $sch) {
                $stmt3 = $conn->prepare("
                    SELECT COUNT(*) FROM appointments 
                    WHERE appointment_date=? 
                    AND time_slot BETWEEN ? AND ? 
                    AND status IN ('Pending','Approved')
                ");
                $stmt3->execute([$date, $sch['start_time'], $sch['end_time']]);
                $booked = $stmt3->fetchColumn();
                $availableSlots += max($sch['slots'] - $booked, 0);
            }

            $titleText = $availableSlots > 0 ? "Available" : "Fully Book";
            $color = $availableSlots > 0 ? '#2ecc71' : '#e74c3c';

            $events[] = [
                'title' => $titleText,
                'start' => $date,
                'color' => $color,
                'allDay' => true,
                'extendedProps' => [
                    'slots' => $availableSlots,
                    'isHoliday' => false
                ]
            ];
        }

        echo json_encode($events);
        exit;
    }

    if ($action === 'slots') {
        $date = $_GET['date'] ?? '';
        $user_id = $_GET['user_id'] ?? 0;

        if (!$date) {
            echo json_encode([
                'allSlots' => [],
                'bookedSlots' => [],
                'userBookedSlots' => []
            ]);
            exit;
        }

        // Get schedules for that date
        $stmt = $conn->prepare("SELECT * FROM schedules WHERE slots_date=? AND status='available'");
        $stmt->execute([$date]);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $allSlots = [];
        foreach ($schedules as $schedule) {
            $start = strtotime($schedule['start_time']);
            $end = strtotime($schedule['end_time']);
            $duration = (int)$schedule['duration'];

            if ($duration <= 0) continue;

            while ($start + $duration*60 <= $end) {
                $slotStart = date("h:i A", $start);
                $slotEnd   = date("h:i A", $start + $duration*60);
                $allSlots[] = "$slotStart - $slotEnd";
                $start += $duration*60;
            }
        }

        // Booked slots (all users)
        $stmt = $conn->prepare("SELECT time_slot FROM appointments 
                                WHERE appointment_date=? 
                                AND status IN ('Pending','Approved')");
        $stmt->execute([$date]);
        $bookedSlots = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Booked slots (current user only)
        $userBookedSlots = [];
        if ($user_id && $user_id !== '0') {
            $stmt = $conn->prepare("SELECT time_slot FROM appointments 
                                    WHERE appointment_date=? 
                                    AND user_id=? 
                                    AND status IN ('Pending','Approved')");
            $stmt->execute([$date, $user_id]);
            $userBookedSlots = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }

        echo json_encode([
            'allSlots' => $allSlots,
            'bookedSlots' => $bookedSlots,
            'userBookedSlots' => $userBookedSlots
        ]);
        exit;
    }

    // Invalid action
    http_response_code(400);
    echo json_encode(['error' => 'Invalid action']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}