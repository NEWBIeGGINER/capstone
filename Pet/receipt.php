<?php
require_once 'libs/fpdf.php';
require_once 'components/connect.php';
require_once 'components/auth.php';

if (!isset($_GET['transaction_no'])) die("Invalid transaction number.");
$transaction_no = $_GET['transaction_no'];

$stmt = $conn->prepare("
    SELECT 
        t.transaction_no, 
        t.service, 
        t.appointment_date, 
        t.time_slot, 
        a.date_seen, 
        a.id AS appointment_id,
        u.name AS customer_name,
        u.phone AS contact,
        u.address,
        CASE 
            WHEN t.status != '' THEN t.status
            WHEN a.status != '' THEN a.status
            ELSE 'Pending'
        END AS apt_status
    FROM transactions t
    LEFT JOIN appointments a 
        ON t.user_id = a.user_id 
       AND t.appointment_date = a.appointment_date 
       AND t.time_slot = a.time_slot
    LEFT JOIN users u ON t.user_id = u.id
    WHERE t.user_id = ? AND t.transaction_no = ?
    LIMIT 1
");
$stmt->execute([$user_id, $transaction_no]);
$transaction = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$transaction) die("Transaction not found.");

class PDF extends FPDF {
    function Circle($x, $y, $r, $style='D') {
        $this->_Arc($x+$r, $y, $x, $y+$r, $x-$r, $y);
        $this->_Arc($x-$r, $y, $x, $y-$r, $x+$r, $y);
        if($style=='F') $op='f';
        elseif($style=='FD' || $style=='DF') $op='b';
        else $op='s';
        $this->_out($op);
    }
    function _Arc($x1, $y1, $x2, $y2, $x3, $y3) {
        $h = $this->h;
        $this->_out(sprintf('%.2F %.2F %.2F %.2F %.2F %.2F c',
            $x1 * $this->k, ($h - $y1) * $this->k,
            $x2 * $this->k, ($h - $y2) * $this->k,
            $x3 * $this->k, ($h - $y3) * $this->k
        ));
    }
}

$pdf = new PDF();
$pdf->AddPage();
$pdf->SetAutoPageBreak(true, 15);

$primary = [0, 102, 204];
$highlight = [255, 235, 59];
$dark = [40, 40, 40];

// 🐾 HEADER
$pdf->SetFont('Arial', 'B', 20);
$pdf->SetTextColor($primary[0], $primary[1], $primary[2]);
$pdf->Cell(0, 12, '🐾 PetCare Veterinary Clinic', 0, 1, 'C');
$pdf->SetFont('Arial', '', 11);
$pdf->Cell(0, 6, 'Pulilan, Bulacan | Contact: 0999-999-9999', 0, 1, 'C');
$pdf->Ln(8);
$pdf->SetDrawColor($primary[0], $primary[1], $primary[2]);
$pdf->Line(20, $pdf->GetY(), 190, $pdf->GetY());
$pdf->Ln(8);

// RECEIPT TITLE
$pdf->SetFillColor($primary[0], $primary[1], $primary[2]);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 12, 'ONLINE APPOINTMENT RECEIPT', 0, 1, 'C', true);
$pdf->Ln(6);

// DETAILS
$pdf->SetTextColor(0, 0, 0);
$pdf->SetFont('Arial', '', 11);
$details = [
    'Patient ID:' => $transaction['appointment_id'] ?? 'N/A',
    'Customer Name:' => $transaction['customer_name'],
    'Appointment Type:' => $transaction['service'],
    'Appointment Date:' => date('l, F j, Y', strtotime($transaction['appointment_date'])),
    'Time Slot:' => $transaction['time_slot'],
    'Status:' => ucfirst($transaction['apt_status'])
];
foreach ($details as $label => $value) {
    $pdf->Cell(50, 8, $label, 0, 0);
    $pdf->SetFont('Arial', 'B', 11);
    $pdf->Cell(0, 8, $value, 0, 1);
    $pdf->SetFont('Arial', '', 11);
}
$pdf->Ln(15);

// 🌟 CENTERED TRANSACTION BOX (mid-page highlight)
$pdf->SetFillColor($highlight[0], $highlight[1], $highlight[2]);
$pdf->SetDrawColor(200, 200, 0);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'TRANSACTION NUMBER', 0, 1, 'C', true);
$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 22);
$pdf->SetTextColor($primary[0], $primary[1], $primary[2]);
$pdf->Cell(0, 14, strtoupper($transaction['transaction_no']), 0, 1, 'C');
$pdf->Ln(20);

// FOOTER MESSAGE
$pdf->SetTextColor(70, 70, 70);
$pdf->SetFont('Arial', 'I', 10);
$pdf->Cell(0, 7, 'Thank you for trusting 🐾 PetCare Veterinary Clinic!', 0, 1, 'C');
$pdf->Cell(0, 6, 'Please present this receipt during your appointment.', 0, 1, 'C');
$pdf->Ln(10);

// SEAL
$x = 105;
$y = 245;
$r = 15;
$pdf->SetDrawColor($primary[0], $primary[1], $primary[2]);
$pdf->SetLineWidth(0.8);
$pdf->Circle($x, $y, $r);
$pdf->SetFont('Arial', 'B', 18);
$pdf->SetTextColor($primary[0], $primary[1], $primary[2]);
$pdf->SetXY($x - 5, $y - 6);
$pdf->Cell(10, 10, '🐾', 0, 0, 'C');
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(50, 50, 50);
$pdf->SetXY($x - 17, $y + 12);
$pdf->Cell(34, 5, 'Verified by Vet', 0, 0, 'C');

// Timestamp
$pdf->Ln(20);
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(120, 120, 120);
$pdf->Cell(0, 5, 'Generated on: ' . date('F j, Y g:i A'), 0, 1, 'C');

// Output
$pdf->Output('I', 'PetCare_Receipt_'.$transaction['transaction_no'].'.pdf');
?>
