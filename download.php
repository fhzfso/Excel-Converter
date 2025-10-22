<?php
session_start();

$fileActiveName = $_SESSION['active_file'] ?? null;
$path = ($fileActiveName !== null && file_exists('excel/' . $fileActiveName)) ? 'excel/' . $fileActiveName : 'excel/data.xlsx';

if (!file_exists($path)) {
    die("File tidak ditemukan.");
}

header('Content-Description: File Transfer');
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . basename($path) . '"');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($path));
readfile($path);
exit;
