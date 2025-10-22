<?php
session_start();
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $dataInput = $_POST['data'] ?? [];
    $saveOption = $_POST['save_option'] ?? 'new';
    $newFilename = trim($_POST['new_filename'] ?? '');

    // Validasi input data
    if (empty($dataInput) || !is_array($dataInput)) {
        $_SESSION['error'] = "Data input kosong atau tidak valid.";
        header('Location: index.php');
        exit;
    }

    // Ambil header dari session custom_headers atau file aktif
    $headers = $_SESSION['custom_headers'] ?? null;
    if (!$headers) {
        if (isset($_SESSION['active_file']) && file_exists('excel/' . $_SESSION['active_file'])) {
            try {
                $spreadsheetTmp = IOFactory::load('excel/' . $_SESSION['active_file']);
                $sheetTmp = $spreadsheetTmp->getActiveSheet();
                $dataTmp = $sheetTmp->toArray();
                $headers = $dataTmp[0] ?? [];
            } catch (Exception $e) {
                $_SESSION['error'] = "Gagal memuat header dari file aktif: " . $e->getMessage();
                header('Location: index.php');
                exit;
            }
        }
    }

    if (!$headers || count($headers) === 0) {
        $_SESSION['error'] = "Header tidak ditemukan. Mohon atur header terlebih dahulu.";
        header('Location: index.php');
        exit;
    }

    // Validasi jumlah kolom data sama dengan header
    if (count($dataInput) !== count($headers)) {
        $_SESSION['error'] = "Jumlah data yang dimasukkan tidak sesuai dengan jumlah header.";
        header('Location: index.php');
        exit;
    }

    // Tentukan file tujuan simpan
    if ($saveOption === 'same' && isset($_SESSION['active_file'])) {
        $targetFile = 'excel/' . $_SESSION['active_file'];
    } else {
        if ($newFilename === '') {
            $_SESSION['error'] = "Nama file baru harus diisi jika menyimpan sebagai file baru.";
            header('Location: index.php');
            exit;
        }
        // Bersihkan nama file dari karakter tidak valid
        $cleanFilename = preg_replace('/[^a-zA-Z0-9_-]/', '', $newFilename);
        $targetFile = 'excel/' . $cleanFilename . '.xlsx';

        // Cek jika file sudah ada, bisa kasih peringatan / overwrite
        if (file_exists($targetFile)) {
            $_SESSION['error'] = "File dengan nama tersebut sudah ada. Gunakan nama lain.";
            header('Location: index.php');
            exit;
        }
    }

    // Load spreadsheet atau buat baru
    if (file_exists($targetFile)) {
        try {
            $spreadsheet = IOFactory::load($targetFile);
        } catch (Exception $e) {
            $_SESSION['error'] = "Gagal membuka file target: " . $e->getMessage();
            header('Location: index.php');
            exit;
        }
    } else {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        // tulis header ke baris pertama
        foreach ($headers as $colIndex => $header) {
            $sheet->setCellValueByColumnAndRow($colIndex + 1, 1, $header);
        }
    }

    $sheet = $spreadsheet->getActiveSheet();

    // Cari baris terakhir terisi dan tambah 1
    $highestRow = $sheet->getHighestRow();
    $nextRow = $highestRow + 1;

    // Tulis data input ke baris berikutnya
    foreach ($dataInput as $colIndex => $cellValue) {
        $sheet->setCellValueByColumnAndRow($colIndex + 1, $nextRow, $cellValue);
    }

    // Simpan file
    try {
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($targetFile);
    } catch (Exception $e) {
        $_SESSION['error'] = "Gagal menyimpan file: " . $e->getMessage();
        header('Location: index.php');
        exit;
    }

    // Setelah simpan, update session dan header
    try {
        $spreadsheetNew = IOFactory::load($targetFile);
        $sheetNew = $spreadsheetNew->getActiveSheet();
        $dataNew = $sheetNew->toArray();
        $newHeaders = $dataNew[0] ?? $headers;
    } catch (Exception $e) {
        $newHeaders = $headers; // fallback
    }

    if ($saveOption === 'new') {
        $_SESSION['active_file'] = basename($targetFile);
        $_SESSION['active_file_original'] = basename($targetFile);
        unset($_SESSION['custom_headers']);
    } elseif ($saveOption === 'same') {
        $_SESSION['custom_headers'] = $newHeaders;
        if (!isset($_SESSION['active_file_original'])) {
            $_SESSION['active_file_original'] = basename($_SESSION['active_file']);
        }
    } else {
        $_SESSION['custom_headers'] = $newHeaders;
    }
    
$_SESSION['success'] = "Data berhasil disimpan ke file " . htmlspecialchars(basename($targetFile)) . ".";
header('Location: index.php');
exit;


} else {
    header('Location: index.php');
    exit;
}
