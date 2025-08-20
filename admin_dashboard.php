<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require 'config.php';

// Wajib admin
if (empty($_SESSION['user']) || strtolower($_SESSION['user']['role']) !== 'admin') {
    header("Location: login.php");
    exit;
}

// ===== Ambil halaman aktif =====
$page = $_GET['page'] ?? 'uploads';

// ===== Aksi =====
if (isset($_GET['approve_id'])) {
    $id = (int)$_GET['approve_id'];
    $conn->query("UPDATE users SET status='approved' WHERE id=$id AND role='user'");
    header("Location: admin_dashboard.php?page=pending");
    exit;
}
if (isset($_GET['reject_id'])) {
    $id = (int)$_GET['reject_id'];
    $conn->query("UPDATE users SET status='rejected' WHERE id=$id AND role='user'");
    header("Location: admin_dashboard.php?page=pending");
    exit;
}
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    $res = $conn->query("SELECT filename FROM uploads WHERE id=$id");
    if ($res && $res->num_rows > 0) {
        $row = $res->fetch_assoc();
        $file = 'uploads/' . $row['filename'];
        if (file_exists($file)) unlink($file);
    }
    $conn->query("DELETE FROM uploads WHERE id=$id");
    header("Location: admin_dashboard.php?page=uploads");
    exit;
}
if (isset($_GET['delete_user_id'])) {
    $id = (int)$_GET['delete_user_id'];
    $res = $conn->query("SELECT filename FROM uploads WHERE user_id=$id");
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $file = 'uploads/' . $row['filename'];
            if (file_exists($file)) unlink($file);
        }
    }
    $conn->query("DELETE FROM uploads WHERE user_id=$id");
    $conn->query("DELETE FROM users WHERE id=$id AND role='user'");
    header("Location: admin_dashboard.php?page=users");
    exit;
}

// ===== Ambil data sesuai halaman =====
if ($page === 'users') {
    $allUsers = $conn->query("SELECT id, username, status FROM users WHERE role='user' ORDER BY id ASC");
} elseif ($page === 'pending') {
    $pendingUsers = $conn->query("SELECT id, username, status FROM users WHERE role='user' AND status='pending' ORDER BY id ASC");
} else { // uploads
    $result = $conn->query("SELECT u.*, us.username 
        FROM uploads u 
        JOIN users us ON u.user_id = us.id 
        ORDER BY u.uploaded_at DESC");
    if ($result === false) $query_error = $conn->error;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Dashboard Admin</title>
<style>
/* ===== STYLING ===== */
:root{--bg:#0b1320;--glass:rgba(255,255,255,.14);--glass-2:rgba(255,255,255,.22);--text:#eaf0ff;--muted:#c9d3ff;--brand:#2ecc71;--brand-2:#27ae60;--danger:#e74c3c;--warn:#f39c12;--info:#3498db;--card-radius:18px;--btn-radius:10px;--shadow:0 10px 30px rgba(0,0,0,.25);--shadow-soft:0 6px 18px rgba(0,0,0,.15);--border:1px solid rgba(255,255,255,.18);--backdrop:blur(10px);}
*{box-sizing:border-box;}html,body{height:100%;margin:0;font-family:ui-sans-serif,system-ui,-apple-system,Segoe UI,Roboto,Arial,"Noto Sans","Helvetica Neue",sans-serif;color:var(--text);background:var(--bg);line-height:1.5;}
.bg-wrap{position:fixed;inset:0;background:linear-gradient(180deg,rgba(7,10,22,.85) 0%,rgba(7,10,22,.6) 100%),url('images/bg.jpg') center/cover no-repeat;z-index:-1;}
.container{max-width:1200px;margin:40px auto 80px;padding:0 18px;}
.card{background:linear-gradient(180deg,var(--glass),var(--glass-2));border:var(--border);border-radius:var(--card-radius);box-shadow:var(--shadow);backdrop-filter:var(--backdrop);overflow:hidden;margin-bottom:20px;}
.card-header{padding:18px 22px;display:flex;align-items:center;justify-content:space-between;border-bottom:var(--border);}
.title{margin:0;font-size:22px;letter-spacing:.3px;}
.actions{display:flex;gap:10px;flex-wrap:wrap;}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:10px 14px;border-radius:var(--btn-radius);font-weight:600;color:#fff;text-decoration:none;border:0;cursor:pointer;box-shadow:var(--shadow-soft);transition:transform .12s ease,opacity .12s ease,filter .12s ease;user-select:none;will-change:transform;}
.btn:disabled{opacity:.5;cursor:not-allowed;filter:grayscale(.2);}
.btn:hover{transform:translateY(-1px);}
.btn:active{transform:translateY(0);}
.btn-brand{background:linear-gradient(135deg,var(--brand),var(--brand-2));}
.btn-info{background:linear-gradient(135deg,#5dade2,var(--info));}
.btn-warn{background:linear-gradient(135deg,#f8c471,var(--warn));color:#18202a;}
.btn-danger{background:linear-gradient(135deg,#f1948a,var(--danger));}
.btn-ghost{background:transparent;border:var(--border);color:var(--muted);}
.toolbar{display:grid;grid-template-columns:1fr auto;gap:14px;padding:16px 22px;}
.search{position:relative;}
.search input{width:100%;padding:12px 14px 12px 38px;border-radius:12px;background:rgba(255,255,255,.12);border:var(--border);color:var(--text);outline:none;box-shadow:inset 0 1px 0 rgba(255,255,255,.06);}
.search .icon{position:absolute;left:10px;top:50%;transform:translateY(-50%);opacity:.75;font-size:14px;}
.table-wrap{overflow:auto;}
table{width:100%;border-collapse:collapse;min-width:900px;}
thead th{position:sticky;top:0;z-index:1;background:rgba(7,10,22,.7);backdrop-filter:blur(6px);-webkit-backdrop-filter:blur(6px);color:var(--muted);font-size:13px;text-transform:uppercase;letter-spacing:.6px;}
th,td{padding:12px 14px;border-bottom:1px solid rgba(255,255,255,.12);}
tbody tr:hover{background:rgba(255,255,255,.06);}
.muted{color:var(--muted);font-size:13px;}
.preview-img{width:90px;height:64px;object-fit:cover;border-radius:10px;border:1px solid rgba(255,255,255,.2);}
.preview-video{width:140px;height:90px;border-radius:10px;border:1px solid rgba(255,255,255,.2);}
.row-actions{display:flex;gap:8px;flex-wrap:wrap;}
.footer-actions{padding:16px 22px;display:flex;gap:10px;align-items:center;justify-content:space-between;border-top:var(--border);background:rgba(0,0,0,.08);}
.left-actions,.right-actions{display:flex;gap:10px;flex-wrap:wrap;}
.badge{font-size:12px;padding:4px 8px;border-radius:999px;border:1px solid rgba(255,255,255,.22);color:var(--muted);}
.badge.image{border-color:#58d68d;color:#58d68d;}
.badge.video{border-color:#5dade2;color:#5dade2;}
.badge.other{border-color:#f8c471;color:#f8c471;}
</style>
</head>
<body>
<div class="bg-wrap" aria-hidden="true"></div>
<div class="container">

<!-- ===== NAVIGASI HALAMAN ===== -->
<div class="card">
    <div class="card-header">
        <h2 class="title">Dashboard Admin</h2>
        <div class="actions">
            <a class="btn btn-ghost" href="admin_dashboard.php?page=users">👥 Semua User</a>
            <a class="btn btn-ghost" href="admin_dashboard.php?page=pending">🕒 Persetujuan User Baru</a>
            <a class="btn btn-ghost" href="admin_dashboard.php?page=uploads">📂 Uploads</a>
            <a class="btn btn-danger" href="logout.php" onclick="return confirm('Keluar?')">🚪 Logout</a>
        </div>
    </div>
</div>

<!-- ===== KONTEN HALAMAN ===== -->
<?php if ($page === 'users'): ?>
    <div class="card">
        <div class="card-header"><h2 class="title">Semua User</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>ID</th><th>Username</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php if ($allUsers && $allUsers->num_rows>0): ?>
                        <?php while($u=$allUsers->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int)$u['id'] ?></td>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td><?= htmlspecialchars($u['status']) ?></td>
                                <td>
                                    <a class="btn btn-danger" href="admin_dashboard.php?delete_user_id=<?= (int)$u['id'] ?>" onclick="return confirm('Yakin hapus akun?')">🗑 Hapus</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="muted">Belum ada user.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($page === 'pending'): ?>
    <div class="card">
        <div class="card-header"><h2 class="title">Persetujuan User Baru</h2></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>ID</th><th>Username</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                    <?php if ($pendingUsers && $pendingUsers->num_rows>0): ?>
                        <?php while($u=$pendingUsers->fetch_assoc()): ?>
                            <tr>
                                <td><?= (int)$u['id'] ?></td>
                                <td><?= htmlspecialchars($u['username']) ?></td>
                                <td><?= htmlspecialchars($u['status']) ?></td>
                                <td>
                                    <a class="btn btn-brand" href="admin_dashboard.php?approve_id=<?= (int)$u['id'] ?>">✅ Setujui</a>
                                    <a class="btn btn-warn" href="admin_dashboard.php?reject_id=<?= (int)$u['id'] ?>">❌ Tolak</a>
                                    <a class="btn btn-danger" href="admin_dashboard.php?delete_user_id=<?= (int)$u['id'] ?>" onclick="return confirm('Yakin hapus akun?')">🗑 Hapus Akun</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="muted">Tidak ada user baru menunggu persetujuan.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php else: ?>
    <!-- ===== HALAMAN UPLOAD ===== -->
    <div class="card">
        <div class="card-header">
            <h2 class="title">Semua Upload</h2>
            <div class="actions">
                <a class="btn btn-info" href="upload.php">📤 Upload Baru</a>
            </div>
        </div>

        <div class="toolbar">
            <div class="search">
                <span class="icon">🔎</span>
                <input type="text" id="q" placeholder="Cari uploader, deskripsi, jenis, nama file…">
            </div>
        </div>

        <form method="post" action="download_selected.php" id="downloadForm">
            <div class="table-wrap">
                <table id="tableUploads">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="selectAll"></th>
                            <th>ID</th>
                            <th>Uploader</th>
                            <th>Preview</th>
                            <th>Deskripsi</th>
                            <th>Jenis</th>
                            <th>Ukuran</th>
                            <th>Waktu Upload</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (isset($query_error)): ?>
                            <tr><td colspan="9" class="muted">Gagal memuat data: <?= htmlspecialchars($query_error) ?></td></tr>
                        <?php elseif (!$result || $result->num_rows === 0): ?>
                            <tr><td colspan="9" class="muted">Belum ada data upload.</td></tr>
                        <?php else: ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><input type="checkbox" name="selected_ids[]" class="row-check" value="<?= (int)$row['id'] ?>"></td>
                                    <td><?= (int)$row['id'] ?></td>
                                    <td><?= htmlspecialchars($row['username']) ?></td>
                                    <td>
                                        <?php if ($row['filetype']==='image'): ?>
                                            <img class="preview-img" src="uploads/<?= htmlspecialchars($row['filename']) ?>" alt="Foto">
                                        <?php elseif ($row['filetype']==='video'): ?>
                                            <video class="preview-video" controls preload="metadata">
                                                <source src="uploads/<?= htmlspecialchars($row['filename']) ?>">
                                            </video>
                                        <?php else: ?>
                                            <span class="badge other">other</span>
                                            <div class="muted"><?= htmlspecialchars($row['filename']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= nl2br(htmlspecialchars($row['description'] ?? '')) ?></td>
                                    <td><span class="badge <?= htmlspecialchars($row['filetype']) ?>"><?= htmlspecialchars($row['filetype']) ?></span></td>
                                    <td><?= number_format((int)$row['filesize']) ?> B</td>
                                    <td><?= htmlspecialchars($row['uploaded_at']) ?></td>
                                    <td class="row-actions">
                                        <a class="btn btn-info" href="view.php?id=<?= (int)$row['id'] ?>">Lihat</a>
                                        <a class="btn btn-warn" href="edit.php?id=<?= (int)$row['id'] ?>">Edit</a>
                                        <a class="btn btn-danger" href="admin_dashboard.php?delete_id=<?= (int)$row['id'] ?>" onclick="return confirm('Yakin hapus file ini?')">Hapus</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <div class="footer-actions">
                <div class="left-actions">
                    <span class="muted" id="selectedCount">0 dipilih</span>
                </div>
                <div class="right-actions">
                    <button type="submit" name="type" value="word" class="btn btn-ghost" disabled id="btnWord">⬇ Word Terpilih</button>
                    <button type="submit" name="type" value="excel" class="btn btn-brand" disabled id="btnExcel">⬇ Excel Terpilih</button>
                    <button type="submit" name="type" value="zip" class="btn btn-info" disabled id="btnZip">⬇ ZIP Terpilih</button>
                </div>
            </div>
        </form>
    </div>
<?php endif; ?>

</div> <!-- container -->

<script>
const selectAll = document.getElementById('selectAll');
const checks = () => Array.from(document.querySelectorAll('.row-check'));
const selectedCount = document.getElementById('selectedCount');
const btns = [document.getElementById('btnWord'), document.getElementById('btnExcel'), document.getElementById('btnZip')];

function refreshState(){
    const c = checks();
    const picked = c.filter(x => x.checked);
    selectedCount.textContent = picked.length + " dipilih";
    btns.forEach(b => b.disabled = picked.length === 0);
    if (picked.length === 0){ selectAll.indeterminate = false; selectAll.checked = false; }
    else if (picked.length === c.length){ selectAll.indeterminate = false; selectAll.checked = true; }
    else { selectAll.indeterminate = true; }
}

selectAll?.addEventListener('change', e => { checks().forEach(x => x.checked = e.target.checked); refreshState(); });
document.addEventListener('change', e => { if (e.target.classList.contains('row-check')) refreshState(); });
refreshState();

const q = document.getElementById('q');
const rows = () => Array.from(document.querySelectorAll('#tableUploads tbody tr'));
q?.addEventListener('input', () => {
    const term = q.value.trim().toLowerCase();
    rows().forEach(tr => { tr.style.display = tr.innerText.toLowerCase().includes(term) ? '' : 'none'; });
});
</script>
</body>
</html>
