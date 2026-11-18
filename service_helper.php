<?php
$serviceOptions = [
    'Engine Oil Change & Filter',
    'Major Service',
    'Brake Pad Replacement',
    'Tire Alignment & Balancing',
    'Air Conditioner Servicing',
    'General Inspection & Diagnostics',
    'Walk-In Inspection'
];

$majorServicePackages = [
    '10k km Package',
    '100k km Package'
];

$oilTypes = [
    'Semi Synthetic',
    'Fully Synthetic'
];

$timeSlots = [
    '09:00:00' => '09:00 AM',
    '10:00:00' => '10:00 AM',
    '11:00:00' => '11:00 AM',
    '13:00:00' => '01:00 PM',
    '14:00:00' => '02:00 PM',
    '15:00:00' => '03:00 PM',
    '16:00:00' => '04:00 PM',
];

function getServiceLaborRate(string $serviceType, ?string $servicePackage = null, ?string $oilType = null): float {
    $baseRates = [
        'Engine Oil Change & Filter'       => 60.00,
        'Major Service'                    => 180.00,
        'Brake Pad Replacement'            => 90.00,
        'Tire Alignment & Balancing'       => 70.00,
        'Air Conditioner Servicing'        => 110.00,
        'General Inspection & Diagnostics' => 80.00,
        'Walk-In Inspection'               => 50.00,
    ];

    $rate = $baseRates[$serviceType] ?? 80.00;

    if ($serviceType === 'Major Service') {
        if ($servicePackage === '100k km Package') {
            $rate = 320.00;
        } elseif ($servicePackage === '10k km Package') {
            $rate = 180.00;
        }
    }

    if ($oilType === 'Fully Synthetic') {
        $rate += 60.00;
    } elseif ($oilType === 'Semi Synthetic') {
        $rate += 25.00;
    }

    return $rate;
}

function formatServiceSummary(string $serviceType, ?string $servicePackage = null, ?string $oilType = null): string {
    $parts = [$serviceType];
    if (!empty($servicePackage)) {
        $parts[] = $servicePackage;
    }
    if (!empty($oilType)) {
        $parts[] = "Oil: " . $oilType;
    }
    return implode(' | ', $parts);
}

function ensureRepairJobAndInvoice(PDO $pdo, int $bookingId, string $serviceType, ?string $servicePackage = null, ?string $oilType = null): int {
    $chk = $pdo->prepare("SELECT JobID FROM RepairJob WHERE BookingID = ?");
    $chk->execute([$bookingId]);
    $existing = $chk->fetch();

    if ($existing) {
        $jobId = (int)$existing['JobID'];
    } else {
        $summary = formatServiceSummary($serviceType, $servicePackage, $oilType);
        $desc = "Scheduled Service: " . $summary;
        $insJob = $pdo->prepare("INSERT INTO RepairJob (BookingID, Description, Status) VALUES (?, ?, 'Pending')");
        $insJob->execute([$bookingId, $desc]);
        $jobId = (int)$pdo->lastInsertId();
    }

    $chkInv = $pdo->prepare("SELECT InvoiceID FROM Invoice WHERE JobID = ?");
    $chkInv->execute([$jobId]);
    if (!$chkInv->fetch()) {
        $laborCost = getServiceLaborRate($serviceType, $servicePackage, $oilType);
        $insInv = $pdo->prepare("INSERT INTO Invoice (JobID, LaborCost, PaymentStatus) VALUES (?, ?, 'Unpaid')");
        $insInv->execute([$jobId, $laborCost]);
    }

    return $jobId;
}
?>