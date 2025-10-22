<?php
session_start();

if (isset($_GET['reset']) && $_GET['reset'] == '1') {
    unset($_SESSION['active_file']);
    unset($_SESSION['active_file_original']);
    unset($_SESSION['custom_headers']);
    $_SESSION['success'] = 'Form berhasil direset!';
    header('Location: index.php');
    exit;
}

require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

$headers = [];
$data = [];
$fileActiveName = $_SESSION['active_file'] ?? null;

if ($fileActiveName !== null && file_exists('excel/' . $fileActiveName)) {
    $spreadsheet = IOFactory::load('excel/' . $fileActiveName);
    $data = $spreadsheet->getActiveSheet()->toArray();
    if (!empty($data)) {
        $headers = $data[0];  // Ambil baris pertama sebagai header default
    }
}

// Jika user pernah set header manual, gunakan header itu (override)
if (isset($_SESSION['custom_headers']) && is_array($_SESSION['custom_headers']) && count($_SESSION['custom_headers']) > 0) {
    $headers = $_SESSION['custom_headers'];
}

$isCustomFileActive = ($fileActiveName !== null && basename($fileActiveName) !== 'data.xlsx');

// Pesan sukses dan error (buat toast)
$successMsg = $_SESSION['success'] ?? null;
$errorMsg = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Converter Excel Pages</title>
      <link rel="icon" type="image/png" href="skenpat.png">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<style>
  /* --- Style seperti milikmu, tidak saya ubah --- */
/* === Global Style === */
body {
  background: radial-gradient(circle at top, #1a1a1a, #0b0b0b);
  font-family: 'Poppins', sans-serif;
  color: #f4f4f4;
  margin: 0;
  padding: 0;
  overflow-x: hidden;
}

/* === Navbar === */
nav {
  background: rgba(0, 0, 0, 0.9);
  backdrop-filter: blur(6px);
  padding: 1rem 2.5rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

nav a {
  color: #ff7b00;
  font-weight: 700;
  text-decoration: none;
  font-size: 1.1rem;
  letter-spacing: 0.5px;
}

nav a:hover {
  color: #ffaa33;
}
    
    .nav-left {
  display: flex;
  align-items: center;
  gap: 8px;
}

.nav-logo {
  height: 26px;           /* kecil seperti emoji */
  width: auto;
  object-fit: contain;
  filter: drop-shadow(0 0 2px rgba(255, 123, 0, 0.4));
  transition: transform 0.25s ease;
}

.nav-logo:hover {
  transform: scale(1.15);
}

    .nav-logo {
  opacity: 0;
  animation: fadeIn 0.8s ease forwards;
}
@keyframes fadeIn {
  to { opacity: 1; }
}


/* === Container === */
.container {
  max-width: 1000px;
  margin: 3rem auto;
  background: linear-gradient(145deg, #141414, #1b1b1b);
  border-radius: 20px;
  box-shadow: 0 0 25px rgba(255, 123, 0, 0.1);
  padding: 2.5rem 3rem;
  border: 1px solid rgba(255, 255, 255, 0.05);
  backdrop-filter: blur(4px);
}

    footer {
  text-align: center;
  padding: 2rem 1rem;
  background: #0a0a0a;
  color: #ccc;
  font-size: 0.95rem;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
}

.footer-links {
  margin-bottom: 0.8rem;
}

.footer-links a {
  color: #ff7b00;
  font-weight: 600;
  text-decoration: none;
  margin: 0 15px;
  transition: color 0.3s ease;
}

.footer-links a:hover {
  color: #ffaa33;
}

footer .highlight {
  color: #ff7b00;
  font-weight: 700;
}
    
/* === Card === */
.card {
  background: linear-gradient(160deg, #1b1b1b, #141414);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 16px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
  margin-bottom: 2rem;
  transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.card:hover {
  transform: translateY(-3px);
  box-shadow: 0 8px 25px rgba(255, 123, 0, 0.2);
}

.card-header {
  background: linear-gradient(90deg, #ff7b00, #ff9b2f);
  color: #fff;
  font-weight: 700;
  font-size: 1.1rem;
  padding: 1rem 1.5rem;
  border-radius: 16px 16px 0 0;
  letter-spacing: 0.5px;
}

/* === Form Input === */
.form-label {
  font-weight: 600;
  color: #ffae42;
}

input.form-control {
  border-radius: 10px;
  border: 1px solid rgba(255, 255, 255, 0.1);
  background-color: #1a1a1a;
  color: #eee;
  padding: 0.6rem 1rem;
  transition: 0.3s;
}

input.form-control:focus {
  border-color: #ff7b00;
  box-shadow: 0 0 10px rgba(255, 123, 0, 0.4);
  background-color: #202020;
}

/* === Buttons === */
button.btn,
.btn {
  background: linear-gradient(90deg, #ff7b00, #ff9b2f);
  border: none;
  color: white;
  border-radius: 25px;
  padding: 0.6rem 1.5rem;
  font-weight: 700;
  cursor: pointer;
  transition: all 0.3s ease;
  box-shadow: 0 3px 10px rgba(255, 123, 0, 0.2);
}

button.btn:hover,
.btn:hover {
  background: linear-gradient(90deg, #ff9b2f, #ffb347);
  box-shadow: 0 0 15px rgba(255, 155, 47, 0.4);
  transform: translateY(-2px);
}

/* === Table === */
table {
  width: 100%;
  border-collapse: collapse;
  color: #ddd;
  border-radius: 10px;
  overflow: hidden;
  margin-top: 1rem;
}

thead tr {
  background: linear-gradient(90deg, #ff7b00, #ff9b2f);
  color: #fff;
  font-weight: bold;
}

th, td {
  padding: 0.75rem 1rem;
  text-align: center;
  border-bottom: 1px solid rgba(255, 255, 255, 0.05);
}

tbody tr:nth-child(even) {
  background: rgba(255, 255, 255, 0.03);
}

tbody tr:hover {
  background: rgba(255, 123, 0, 0.1);
}

/* === Toggle Dark Mode Button === */
#modeToggle {
  position: fixed;
  bottom: 25px;
  right: 25px;
  width: 50px;
  height: 50px;
  border-radius: 50%;
  border: none;
  background: linear-gradient(145deg, #ff7b00, #ff9b2f);
  color: white;
  font-size: 22px;
  cursor: pointer;
  box-shadow: 0 0 20px rgba(255, 123, 0, 0.4);
  transition: all 0.3s ease;
}

#modeToggle:hover {
  transform: rotate(15deg) scale(1.05);
  box-shadow: 0 0 25px rgba(255, 155, 47, 0.6);
}
    
    /* === Dark Mode Styles === */
.dark-mode {
  background: radial-gradient(circle at top, #f2f2f2, #d9d9d9);
  color: #222;
}

.dark-mode nav {
  background: rgba(255, 255, 255, 0.9);
  border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.dark-mode nav a {
  color: #ff7b00;
}

.dark-mode .container {
  background: linear-gradient(145deg, #ffffff, #f5f5f5);
  color: #111;
  box-shadow: 0 0 25px rgba(0, 0, 0, 0.1);
}

.dark-mode .card {
  background: linear-gradient(160deg, #ffffff, #f8f8f8);
  color: #111;
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.dark-mode input.form-control {
  background-color: #fff;
  color: #111;
  border: 1px solid rgba(0, 0, 0, 0.1);
}

.dark-mode input.form-control:focus {
  background-color: #fdfdfd;
  box-shadow: 0 0 10px rgba(255, 123, 0, 0.4);
}

.dark-mode table {
  color: #222;
}

.dark-mode thead tr {
  background: linear-gradient(90deg, #ffb347, #ff7b00);
}

.dark-mode tbody tr:nth-child(even) {
  background: rgba(0, 0, 0, 0.03);
}

.dark-mode footer {
  background: #f5f5f5;
  color: #555;
  border-top: 1px solid rgba(0, 0, 0, 0.1);
}

.dark-mode .footer-links a {
  color: #ff7b00;
}


/* === Toast === */
.toast {
  background-color: #1b1b1b !important;
  border-radius: 10px !important;
  border: 1px solid rgba(255, 255, 255, 0.1);
  color: #fff !important;
}

/* === Scrollbar === */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-thumb {
  background: #ff7b00;
  border-radius: 10px;
}

::-webkit-scrollbar-track {
  background: #111;
    
    
}
    
    /* --- Responsive tweaks for mobile --- */
/* Make container fit small screens better */
@media (max-width: 767.98px) {
  .container {
    margin: 1rem;
    padding: 1.25rem;
    border-radius: 12px;
    max-width: calc(100% - 2rem);
  }

  nav {
    padding: 0.6rem 1rem;
  }

  .nav-left a {
    font-size: 1rem;
  }

  .nav-logo {
    height: 22px; /* sedikit lebih kecil di mobile */
  }

  /* Make header/title wrap nicely and push to next line if needed */
  nav .nav-left {
    gap: 10px;
    flex-wrap: wrap;
  }

  /* Forms: stack inputs full width on mobile */
  #dataForm .col-md-4,
  #headerForm .col-12,
  #headerForm .col-auto,
  #dataForm .col-12 {
    flex: 0 0 100%;
    max-width: 100%;
  }

  /* Buttons full width on mobile for easier tapping */
  .card .btn,
  .btn {
    display: block;
    width: 100%;
    padding: 0.9rem 1rem;
    margin-bottom: 0.6rem;
  }

  /* Small adjustments for typography and spacing */
  .card-header { font-size: 1rem; padding: .75rem 1rem; }
  .form-label { font-size: 0.95rem; }
  input.form-control { padding: 0.55rem 0.9rem; }

  /* Reduce heavy shadows on mobile for performance/readability */
  .container, .card { box-shadow: none; }

  /* Make the floating mode toggle smaller and less intrusive */
  #modeToggle {
    bottom: 18px;
    right: 18px;
    width: 44px;
    height: 44px;
    font-size: 18px;
  }

  /* Toast width fit small screens */
  .position-fixed.top-0.end-0.p-3 {
    left: 8px;
    right: 8px;
    top: 8px;
    padding: 0;
  }

  .toast { width: 100%; border-radius: 8px; }
}

/* Make table horizontally scrollable on small screens */
.table-responsive-custom {
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

/* Reduce number of columns visible comfortably on tiny screens:
   keep the same markup but make table cells wrap when necessary */
table td, table th {
  white-space: nowrap;
}

/* Optional: improve readability of table cells on very small devices */
@media (max-width: 420px) {
  th, td { padding: 0.5rem 0.6rem; font-size: 0.85rem; }
}


</style>
</head>
<body>

<nav>
  <div class="nav-left">
            <img src="skenpat.png" alt="SKENPAT" class="nav-logo">


    <a href="#">Converter Excel Pages</a>
  </div>
</nav>


<!-- Toggle Dark Mode Button -->
<button id="modeToggle" aria-label="Toggle Dark Mode" title="Toggle Dark Mode">🌙</button>

<div class="container mt-4">

  <!-- Form Import Excel -->
  <div class="card mb-4">
    <div class="card-header">[optional] Upload File Excel Jika Ada (kalau tidak ada bisa langsung ke bawah)</div>
    <div class="card-body">
      <form method="POST" action="import.php" enctype="multipart/form-data" class="row g-3">
        <div class="col-auto">
          <input type="file" name="excel_file" accept=".xls,.xlsx" required class="form-control" />
        </div>
        <div class="col-auto">
          <button type="submit" class="btn btn-primary">Upload & Load</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Form Set Header Manual -->
  <div class="card mb-4">
    <div class="card-header">⚙️ Atur Header Kolom</div>
    <div class="card-body">
      <form method="POST" action="set_header.php" class="row g-3" id="headerForm">
        <div class="col-12">
          <input
            type="text"
            name="headers"
            class="form-control"
            placeholder="Contoh: Nama,Email,Telepon"
            value="<?php echo !empty($headers) ? htmlspecialchars(implode(',', $headers)) : ''; ?>"
            required
          />
        </div>
        <div class="col-12 text-end">
          <button type="submit" class="btn btn-success">💾 Simpan Header</button>
          <a href="?reset=1" class="btn btn-secondary ms-2">🔄 Reset Form</a>
          <button type="button" class="btn btn-warning ms-2" id="clearHeaderBtn">🧹 Clear Form</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Form Input Data Dinamis -->
  <?php if (!empty($headers)) : ?>
  <div class="card mb-4">
    <div class="card-header">📄 Form Input Data</div>
    <div class="card-body">
      <form method="POST" action="save.php" autocomplete="off" class="row g-3" id="dataForm">
        <?php foreach ($headers as $header): ?>
          <div class="col-md-4">
          <label class="form-label"><?php echo htmlspecialchars($header ?? ''); ?></label><input
  type="text"
  name="data[]"
  class="form-control"
  placeholder="Masukkan <?php echo htmlspecialchars($header ?? ''); ?>"
  required
/>

          </div>
        <?php endforeach; ?>
        
        <div class="col-12 mt-3">
          <label class="form-label">Opsi Penyimpanan</label><br />
          <?php if ($isCustomFileActive): ?>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="save_option" id="save_same" value="same" checked />
              <label class="form-check-label" for="save_same">
                Simpan ke file yang sama (
                <?php
                  echo htmlspecialchars($_SESSION['active_file_original'] ?? basename($fileActiveName));
                ?>
                )
              </label>
            </div>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="save_option" id="save_new" value="new" />
              <label class="form-check-label" for="save_new">Simpan sebagai file baru</label>
            </div>
          <?php else: ?>
            <div class="form-check form-check-inline">
              <input class="form-check-input" type="radio" name="save_option" id="save_new" value="new" checked />
              <label class="form-check-label" for="save_new">Simpan sebagai file baru</label>
            </div>
          <?php endif; ?>
        </div>

        <div class="col-12" id="newFileNameContainer" style="display: <?php echo ($fileActiveName === null || basename($fileActiveName) === 'data.xlsx') ? 'block' : 'none'; ?>;">
          <label class="form-label">Nama File Baru</label>
          <input type="text" name="new_filename" class="form-control" placeholder="contoh: data_baru" />
        </div>

        <div class="col-12 text-end mt-3">
          <button type="submit" class="btn btn-primary">💾 Simpan Data</button>
          <button type="reset" class="btn btn-warning ms-2" id="clearDataBtn">🧹 Clear Form</button>
        </div>
      </form>
    </div>
  </div>
  <?php endif; ?>

  <!-- Tabel Data -->
  <div class="card mb-4">
    <div class="card-header">📊 Data yang Tersimpan</div>
<div class="card-body p-0">
  <div class="table-responsive table-responsive-custom">
    <table class="table table-striped table-bordered mb-0">
        <thead class="table-success">
          <tr>
           <?php foreach ($headers as $h): ?>
  <th><?php echo htmlspecialchars($h ?? ''); ?></th>
<?php endforeach; ?>

          </tr>
        </thead>
        <tbody>
          <?php
          if (count($data) > 1) {
            foreach ($data as $index => $row) {
              if ($index === 0) continue; // skip header
              echo "<tr>";
              foreach ($row as $cell) {
echo "<td>" . htmlspecialchars($cell ?? '') . "</td>";
              }
              echo "</tr>";
            }
          } else {
            echo '<tr><td colspan="' . count($headers) . '" class="text-muted text-center">Belum ada data tersimpan</td></tr>';
          }
          ?>
        </tbody>
      </table>
    </div>
  </div>

<button class="btn btn-success mb-5" id="downloadBtn">⬇️ Download Data</button>

</div>

<!-- Toast Container -->
<div aria-live="polite" aria-atomic="true" class="position-fixed top-0 end-0 p-3" style="z-index: 1055;">
  <div id="liveToast" class="toast align-items-center text-bg-success border-0" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
      <div class="toast-body" id="toastBody">
        <!-- Pesan akan dimasukkan disini -->
      </div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>

  // toast download
document.getElementById('downloadBtn').addEventListener('click', () => {
  showToast('File berhasil di download!');
  
  // Mulai download file
  const link = document.createElement('a');
  link.href = 'download.php';
  link.download = ''; // biarkan nama file dari server
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
});


  // Toast helper function
  function showToast(message, isError = false) {
    const toastEl = document.getElementById('liveToast');
    const toastBody = document.getElementById('toastBody');
    toastBody.textContent = message;
    if (isError) {
      toastEl.classList.remove('text-bg-success');
      toastEl.classList.add('text-bg-danger');
    } else {
      toastEl.classList.remove('text-bg-danger');
      toastEl.classList.add('text-bg-success');
    }
    const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
    toast.show();
  }

  // Tampilkan toast dari PHP session jika ada
  <?php if ($successMsg): ?>
    showToast(<?php echo json_encode($successMsg); ?>, false);
  <?php endif; ?>

  <?php if ($errorMsg): ?>
    showToast(<?php echo json_encode($errorMsg); ?>, true);
  <?php endif; ?>

  // Mode toggle
  const modeToggle = document.getElementById('modeToggle');

  function setMode(dark) {
    if (dark) {
      document.body.classList.add('dark-mode');
      modeToggle.textContent = '☀️';
    } else {
      document.body.classList.remove('dark-mode');
      modeToggle.textContent = '🌙';
    }
    localStorage.setItem('darkMode', dark ? 'true' : 'false');
  }

 const savedMode = localStorage.getItem('darkMode');

// Jika belum ada data di localStorage, default-nya light mode
if (savedMode === null) {
  setMode(false); // false = light mode
} else {
  setMode(savedMode === 'true');
}


  modeToggle.addEventListener('click', () => {
    const isDark = document.body.classList.contains('dark-mode');
    setMode(!isDark);
  });

  // Show/hide input nama file baru sesuai pilihan radio
  document.querySelectorAll('input[name="save_option"]').forEach(radio => {
    radio.addEventListener('change', () => {
      const container = document.getElementById('newFileNameContainer');
      container.style.display = document.getElementById('save_new').checked ? 'block' : 'none';
    });
  });
  document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('newFileNameContainer');
    container.style.display = document.getElementById('save_new').checked ? 'block' : 'none';
  });

  // Tambah event Clear Form header dan data untuk tampilkan toast
  document.getElementById('clearHeaderBtn').addEventListener('click', () => {
    document.querySelector('input[name="headers"]').value = '';
    showToast('Form header berhasil dibersihkan!');
  });
  document.getElementById('clearDataBtn').addEventListener('click', () => {
    document.querySelectorAll('#dataForm input[type=text]').forEach(input => input.value = '');
    showToast('Form data berhasil dibersihkan!');
  });
</script>

<footer>
  <div class="footer-links">
    <a href="https://instagram.com/rapeiii" target="_blank">Developer</a>
    <a href="https://smkn4bjm.sch.id/" target="_blank">Privacy Policy</a>
    <a href="https://smkn4bjm.sch.id/" target="_blank">Terms of Service</a>
  </div>
  <p>© 2025 <span class="highlight">Muhammad Rafe'i</span> | SMKN 4 Banjarmasin</p>
</footer>

</body>
</html>
