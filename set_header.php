<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['headers'])) {
        $headers_raw = trim($_POST['headers']);
        if ($headers_raw !== '') {
            $headers_array = array_map('trim', explode(',', $headers_raw));
            $_SESSION['custom_headers'] = $headers_array;
            // Supaya form input menggunakan header manual
            // Jika ada file aktif, hapus supaya header manual yang dipakai
            unset($_SESSION['active_file']);
            unset($_SESSION['active_file_original']);

            $_SESSION['success'] = "Header kolom berhasil disimpan.";
        } else {
            $_SESSION['error'] = "Header tidak boleh kosong.";
        }
    } else {
        $_SESSION['error'] = "Tidak ada data header yang dikirim.";
    }

    header('Location: index.php');
    exit;
} else {
    header('Location: index.php');
    exit;
}
