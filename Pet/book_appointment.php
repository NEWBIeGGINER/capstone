<?php
require_once 'components/connect.php';
require_once 'components/auth.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}
$user_id = $_SESSION['user_id'];

$action = $_POST['action'] ?? '';

if ($action === 'book_appointment') {
    $date = $_POST['date'] ?? '';
    $time_slot = $_POST['time_slot'] ?? '';
    $service_id = $_POST['service_id'] ?? '';

    if (!$date || !$time_slot || !$service_id) {
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }

    // Validate service
    $stmt = $conn->prepare("SELECT name FROM services WHERE id=?");
    $stmt->execute([$service_id]);
    $service = $stmt->fetchColumn();
    if (!$service) {
        echo json_encode(['success' => false, 'message' => 'Invalid service']);
        exit;
    }

    // Parse time slot
    $times = explode(' - ', $time_slot);
    $start_time = date('H:i', strtotime($times[0]));
    $end_time   = date('H:i', strtotime($times[1]));

    // Check duplicate booking
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM appointments 
        WHERE appointment_date=? AND time_slot=? AND service_id=? AND user_id=? 
        AND status IN ('Pending','Approved')
    ");
    $stmt->execute([$date, $time_slot, $service_id, $user_id]);
    if ($stmt->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'You already booked this slot']);
        exit;
    }

    // Fetch schedule that covers this time slot
    $stmt = $conn->prepare("
        SELECT slots, total_slots 
        FROM schedules 
        WHERE slots_date=? AND start_time<=? AND end_time>=?
    ");
    $stmt->execute([$date, $start_time, $end_time]);
    $sched = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$sched) {
        echo json_encode(['success' => false, 'message' => 'No schedule found for this slot']);
        exit;
    }

    $available_slots = $sched['slots'];
    if ($available_slots <= 0) {
        echo json_encode(['success' => false, 'message' => 'This slot is fully booked']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Generate unique transaction number
        do {
            $transaction_no = "TR-" . date("Ymd") . "-" . rand(100000, 999999);
            $check = $conn->prepare("SELECT 1 FROM transactions WHERE transaction_no=?");
            $check->execute([$transaction_no]);
        } while ($check->fetch());

        // Insert transaction
        $stmt1 = $conn->prepare("
            INSERT INTO transactions 
            (transaction_no, user_id, service, appointment_date, time_slot, status, created_at) 
            VALUES (?, ?, ?, ?, ?, 'Pending', NOW())
        ");
        $stmt1->execute([$transaction_no, $user_id, $service, $date, $time_slot]);

        // Insert appointment
        $stmt2 = $conn->prepare("
            INSERT INTO appointments 
            (transaction_no, user_id, appointment_date, time_slot, status, service_id) 
            VALUES (?, ?, ?, ?, 'Pending', ?)
        ");
        $stmt2->execute([$transaction_no, $user_id, $date, $time_slot, $service_id]);

        // Reduce available slots by 1
        $stmtUpdate = $conn->prepare("
            UPDATE schedules 
            SET slots = slots - 1 
            WHERE slots_date=? AND start_time=? AND end_time=?
        ");
        $stmtUpdate->execute([$date, $start_time, $end_time]);


        $conn->commit();

        echo json_encode([
            'success' => true,
            'transaction_no' => $transaction_no,
            'date' => $date,
            'service' => $service,
            'time_slot' => $time_slot
        ]);

    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success'=>false,'message'=>'Database error: '.$e->getMessage()]);
    }

} elseif ($action === 'cancel') {
    $appointment_id = $_POST['appointment_id'] ?? '';

    if (!$appointment_id) {
        echo json_encode(['success' => false, 'message' => 'Missing appointment ID']);
        exit;
    }

    try {
        $conn->beginTransaction();

        // Get appointment details
        $stmt = $conn->prepare("
            SELECT appointment_date, time_slot, service_id, status 
            FROM appointments 
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([$appointment_id, $user_id]);
        $appt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$appt) {
            $conn->rollBack();
            echo json_encode(['success' => false, 'message' => 'Appointment not found']);
            exit;
        }

        $date = $appt['appointment_date'];
        $time_slot = $appt['time_slot'];
        $status = $appt['status'];

        $adjustSlots = in_array($status, ['Pending','Approved']);

        // Cancel appointment
        $stmt = $conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE id = ?");
        $stmt->execute([$appointment_id]);

        if ($adjustSlots) {
            $times = explode(' - ', $time_slot);
            $start_time = date('H:i', strtotime($times[0]));
            $end_time   = date('H:i', strtotime($times[1]));

            // Increase slots but do not exceed total_slots
            $stmtS = $conn->prepare("
                UPDATE schedules 
                SET slots = slots + 1 
                WHERE slots_date=? AND start_time<=? AND end_time>=? AND slots < total_slots
            ");
            $stmtS->execute([$date, $start_time, $end_time]);
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully', 'date' => $date]);

    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }

} else {
    echo json_encode(['success'=>false,'message'=>'Invalid action']);
}
