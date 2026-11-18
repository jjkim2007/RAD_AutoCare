<?php
require_once 'auth_check.php';
require_once 'dbConnect.php';

$totalCustomers  = $pdo->query("SELECT COUNT(*) FROM Customer WHERE Status = 'Active'")->fetchColumn();
$pendingBookings = $pdo->query("SELECT COUNT(*) FROM Booking WHERE Status = 'Pending'")->fetchColumn();
$activeJobs      = $pdo->query("SELECT COUNT(*) FROM RepairJob WHERE Status IN ('In Progress', 'Awaiting Parts')")->fetchColumn();
$unpaidInvoices  = $pdo->query("SELECT COUNT(*) FROM Invoice WHERE PaymentStatus = 'Unpaid'")->fetchColumn();

// Calculate Total Sales (Paid Invoices: LaborCost + sum of JobPart subtotals)
$salesSql = "SELECT COALESCE(SUM(inv.LaborCost + COALESCE(parts.PartsTotal, 0)), 0) AS TotalSales
             FROM Invoice inv
             LEFT JOIN (
                 SELECT jp.JobID, SUM(jp.QuantityUsed * p.UnitPrice) AS PartsTotal
                 FROM JobPart jp
                 JOIN Part p ON jp.PartID = p.PartID
                 GROUP BY jp.JobID
             ) parts ON inv.JobID = parts.JobID
             WHERE inv.PaymentStatus = 'Paid'";
$totalSales = (float)$pdo->query($salesSql)->fetchColumn();

// Also compute Total Billed (Paid + Unpaid) for complete visibility
$billedSql = "SELECT COALESCE(SUM(inv.LaborCost + COALESCE(parts.PartsTotal, 0)), 0) AS TotalBilled
              FROM Invoice inv
              LEFT JOIN (
                  SELECT jp.JobID, SUM(jp.QuantityUsed * p.UnitPrice) AS PartsTotal
                  FROM JobPart jp
                  JOIN Part p ON jp.PartID = p.PartID
                  GROUP BY jp.JobID
              ) parts ON inv.JobID = parts.JobID";
$totalBilled = (float)$pdo->query($billedSql)->fetchColumn();

require_once 'dashboard_view.html';
?>