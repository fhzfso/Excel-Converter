<?php
session_start();

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = __DIR__ . '/excel/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $tmpName = $_FILES['excel_file']['tmp_name'];
        $originalName = basename($_FILES['excel_file']['name']);
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);

        if (!in_array(strtolower($ext), ['xls', 'xlsx'])) {
            $_SESSION['error'] = "Format file harus .xls atau .xlsx";
            header('Location: index.php');
            exit;
        }

        // Beri nama file unik di server
        $newFileName = 'imported_' . time() . '.' . $ext;
        $targetFile = $uploadDir . $newFileName;

        if (move_uploaded_file($tmpName, $targetFile)) {
            try {
                $spreadsheet = IOFactory::load($targetFile);
                $sheet = $spreadsheet->getActiveSheet();
                $data = $sheet->toArray();

                if (empty($data) || empty($data[0])) {
                    $_SESSION['error'] = "File kosong atau header tidak ditemukan.";
                    unlink($targetFile);
                    header('Location: index.php');
                    exit;
                }

                // Simpan nama file yang aktif (nama file server)
                $_SESSION['active_file'] = $newFileName;

                // Simpan nama asli file upload untuk tampil di UI opsi simpan
                $_SESSION['active_file_original'] = $originalName;

                // Hapus header custom supaya header baca dari file baru
                unset($_SESSION['custom_headers']);

                $_SESSION['success'] = "File Excel berhasil diupload dan dimuat.";

                header('Location: index.php');
                exit;

            } catch (Exception $e) {
                $_SESSION['error'] = "Gagal membaca file Excel: " . $e->getMessage();
                unlink($targetFile);
                header('Location: index.php');
                exit;
            }
        } else {
            $_SESSION['error'] = "Gagal mengupload file.";
            header('Location: index.php');
            exit;
        }
    } else {
        $_SESSION['error'] = "Tidak ada file yang diupload.";
        header('Location: index.php');
        exit;
    }
} else {
    header('Location: index.php');
    exit;
}
