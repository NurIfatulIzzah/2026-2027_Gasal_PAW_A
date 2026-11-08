<?php
session_start();
require __DIR__ . '/config.php';

function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8'); }
function rupiah($value): string { return 'Rp ' . number_format((int)$value, 0, ',', '.'); }
function redirect(string $page, int $id = 0): never {
    $url = 'index.php?page=' . urlencode($page);
    if ($id > 0) $url .= '&id=' . $id;
    header('Location: ' . $url, true, 303); exit;
}
function flash(string $message, string $type = 'success'): void {
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}
function recalcTotal(PDO $pdo, int $transactionId): void {
    $stmt = $pdo->prepare('UPDATE transaksi SET total = (SELECT COALESCE(SUM(harga * qty), 0) FROM transaksi_detail WHERE transaksi_id = ?) WHERE id = ?');
    $stmt->execute([$transactionId, $transactionId]);
}
function positiveInt($value): int {
    $value = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($value === false) throw new RuntimeException('ID atau jumlah tidak valid.');
    return $value;
}
if (!isset($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
$page = $_GET['page'] ?? 'transaksi';
if (!in_array($page, ['transaksi', 'detail', 'barang'], true)) $page = 'transaksi';
$id = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['action'] ?? '');
    $target = in_array($action, ['hapus_barang'], true) ? 'barang' : 'transaksi';
    $targetId = 0;
    try {
        if (!hash_equals($_SESSION['csrf'], (string)($_POST['csrf'] ?? ''))) {
            throw new RuntimeException('Token keamanan tidak valid. Muat ulang halaman lalu coba lagi.');
        }
        if ($action === 'buat_transaksi') {
            $tanggal = (string)($_POST['tanggal'] ?? '');
            $pelangganId = (string)($_POST['pelanggan_id'] ?? '');
            $keterangan = trim((string)($_POST['keterangan'] ?? ''));
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $tanggal);
            if (!$date || $date->format('Y-m-d') !== $tanggal) throw new RuntimeException('Tanggal transaksi tidak valid.');
            if ($pelangganId === '') throw new RuntimeException('Pelanggan wajib dipilih.');
            $stmt = $pdo->prepare('INSERT INTO transaksi (waktu_transaksi, keterangan, total, pelanggan_id) VALUES (?, ?, 0, ?)');
            $stmt->execute([$tanggal, $keterangan, $pelangganId]);
            $target = 'detail';
            $targetId = (int)$pdo->lastInsertId();
            flash('Transaksi baru berhasil dibuat. Silakan tambahkan barang.');
        } elseif ($action === 'tambah_detail') {
            $target = 'detail';
            $targetId = positiveInt($_POST['transaksi_id'] ?? null);
            $barangId = positiveInt($_POST['barang_id'] ?? null);
            $qty = positiveInt($_POST['qty'] ?? null);
            $pdo->beginTransaction();
            // Kunci transaksi induk agar dua input bersamaan diproses berurutan.
            $stmt = $pdo->prepare('SELECT id FROM transaksi WHERE id = ? FOR UPDATE');
            $stmt->execute([$targetId]);
            if (!$stmt->fetch()) throw new RuntimeException('Transaksi tidak ditemukan.');
            $stmt = $pdo->prepare('SELECT harga FROM barang WHERE id = ? FOR UPDATE');
            $stmt->execute([$barangId]);
            $barang = $stmt->fetch();
            if (!$barang) throw new RuntimeException('Barang tidak tersedia.');
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM transaksi_detail WHERE transaksi_id = ? AND barang_id = ?');
            $stmt->execute([$targetId, $barangId]);
            if ((int)$stmt->fetchColumn() > 0) throw new RuntimeException('Barang tersebut sudah ada dalam transaksi ini. Pilih barang lain.');
            $harga = (int)$barang['harga'];
            if ($harga > 0 && $qty > intdiv(2147483647, $harga)) throw new RuntimeException('Total detail melebihi batas nominal yang didukung.');
            $stmt = $pdo->prepare('INSERT INTO transaksi_detail (transaksi_id, barang_id, harga, qty) VALUES (?, ?, ?, ?)');
            $stmt->execute([$targetId, $barangId, $harga, $qty]);
            recalcTotal($pdo, $targetId);
            $pdo->commit();
            flash('Barang berhasil ditambahkan. Total transaksi dihitung ulang otomatis.');
        } elseif ($action === 'hapus_detail') {
            $target = 'detail';
            $targetId = positiveInt($_POST['transaksi_id'] ?? null);
            $barangId = positiveInt($_POST['barang_id'] ?? null);
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT id FROM transaksi WHERE id = ? FOR UPDATE');
            $stmt->execute([$targetId]);
            if (!$stmt->fetch()) throw new RuntimeException('Transaksi tidak ditemukan.');
            $stmt = $pdo->prepare('DELETE FROM transaksi_detail WHERE transaksi_id = ? AND barang_id = ?');
            $stmt->execute([$targetId, $barangId]);
            if ($stmt->rowCount() === 0) throw new RuntimeException('Detail transaksi tidak ditemukan.');
            recalcTotal($pdo, $targetId);
            $pdo->commit();
            flash('Detail dihapus dan total transaksi diperbarui.');
        } elseif ($action === 'hapus_barang') {
            $target = 'barang';
            $barangId = positiveInt($_POST['barang_id'] ?? null);
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('SELECT id, nama_barang FROM barang WHERE id = ? FOR UPDATE');
            $stmt->execute([$barangId]);
            $barang = $stmt->fetch();
            if (!$barang) throw new RuntimeException('Barang tidak ditemukan.');
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM transaksi_detail WHERE barang_id = ?');
            $stmt->execute([$barangId]);
            if ((int)$stmt->fetchColumn() > 0) throw new RuntimeException('Barang "' . $barang['nama_barang'] . '" tidak boleh dihapus karena masih digunakan pada transaksi.');
            $stmt = $pdo->prepare('DELETE FROM barang WHERE id = ?');
            $stmt->execute([$barangId]);
            $pdo->commit();
            flash('Barang "' . $barang['nama_barang'] . '" berhasil dihapus.');
        } else {
            throw new RuntimeException('Aksi tidak dikenal.');
        }
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        if ($e instanceof PDOException && $e->getCode() === '23000') {
            flash('Operasi ditolak karena relasi data atau barang duplikat. Periksa data terkait.', 'error');
        } else {
            flash($e instanceof PDOException ? 'Gagal menyimpan data. Periksa isi dan batas nilai input.' : $e->getMessage(), 'error');
        }
    }
    redirect($target, $targetId);
}

$transaction = null;
$details = [];
$available = [];
$transactions = [];
$items = [];
$customers = [];
if ($page === 'detail') {
    $stmt = $pdo->prepare('SELECT t.*, p.nama AS nama_pelanggan FROM transaksi t JOIN pelanggan p ON p.id = t.pelanggan_id WHERE t.id = ?');
    $stmt->execute([$id]);
    $transaction = $stmt->fetch();
    if ($transaction) {
        $stmt = $pdo->prepare('SELECT d.*, b.kode_barang, b.nama_barang FROM transaksi_detail d JOIN barang b ON b.id = d.barang_id WHERE d.transaksi_id = ? ORDER BY b.nama_barang');
        $stmt->execute([$id]);
        $details = $stmt->fetchAll();
        // Barang yang sudah dipilih disembunyikan dari dropdown (sesuai tugas 2).
        $stmt = $pdo->prepare('SELECT b.id,b.kode_barang,b.nama_barang,b.harga FROM barang b WHERE NOT EXISTS (SELECT 1 FROM transaksi_detail d WHERE d.barang_id = b.id AND d.transaksi_id = ?) ORDER BY b.nama_barang');
        $stmt->execute([$id]);
        $available = $stmt->fetchAll();
    }
} elseif ($page === 'barang') {
    $items = $pdo->query('SELECT b.*, s.nama AS nama_supplier, (SELECT COUNT(*) FROM transaksi_detail d WHERE d.barang_id = b.id) AS digunakan FROM barang b JOIN supplier s ON s.id = b.supplier_id ORDER BY b.id')->fetchAll();
} else {
    $transactions = $pdo->query('SELECT t.*, p.nama AS nama_pelanggan, (SELECT COUNT(*) FROM transaksi_detail d WHERE d.transaksi_id = t.id) AS jumlah_jenis FROM transaksi t JOIN pelanggan p ON p.id = t.pelanggan_id ORDER BY t.id DESC')->fetchAll();
    $customers = $pdo->query('SELECT id,nama FROM pelanggan ORDER BY nama')->fetchAll();
}
$notice = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<?php
$page_title = 'Praktikum Modul 6 - Master Detail Penjualan';
$body_class = 'bg-white';
include __DIR__ . '/components/header.php';
include __DIR__ . '/components/navbar.php';
?>
<section class="bg-[#F8F9FA] border-b border-slate-100 py-9">
 <div class="max-w-7xl mx-auto px-4 sm:px-8">
  <div class="text-xs uppercase tracking-widest text-slate-400 font-bold mb-2">Pengelolaan Master Detil Data</div>
  <h1 class="font-serif text-3xl sm:text-4xl font-extrabold uppercase text-slate-950"><?= $page === 'barang' ? 'Manajemen Data Barang' : ($page === 'detail' ? 'Detail Transaksi' : 'Daftar & Manajemen Transaksi') ?></h1>
  <p class="text-sm text-slate-600 mt-2">Data transaksi sebagai master dan transaksi_detail sebagai detail.</p>
 </div>
</section>
<main class="w-full max-w-7xl mx-auto px-4 sm:px-8 py-10 flex-1 space-y-8">
<?php if ($notice): ?>
<div role="alert" class="border px-4 py-3 text-sm <?= $notice['type'] === 'error' ? 'border-red-300 bg-red-50 text-red-800' : 'border-emerald-300 bg-emerald-50 text-emerald-900' ?>"><?= e($notice['message']) ?></div>
<?php endif; ?>

<?php if ($page === 'transaksi'): ?>
<section class="grid grid-cols-1 sm:grid-cols-2 gap-4">
 <div class="border border-slate-200 p-5"><span class="text-xs uppercase text-slate-400 font-bold">Jumlah transaksi</span><p class="text-2xl font-extrabold mt-1"><?= count($transactions) ?></p></div>
 <div class="border border-slate-200 p-5"><span class="text-xs uppercase text-slate-400 font-bold">Akumulasi nilai transaksi</span><p class="text-2xl font-extrabold mt-1"><?= rupiah(array_sum(array_column($transactions, 'total'))) ?></p></div>
</section>
<section class="border border-slate-200 p-5 sm:p-7">
 <h2 class="font-serif text-xl font-extrabold uppercase mb-4">Tambah Transaksi Baru</h2>
 <form method="post" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
  <input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="buat_transaksi">
  <label class="block">Tanggal transaksi<input class="w-full mt-2 border border-slate-300 px-3 py-2" type="date" name="tanggal" value="<?= date('Y-m-d') ?>" required></label>
  <label class="block">Pelanggan<select class="w-full mt-2 border border-slate-300 bg-white px-3 py-2" name="pelanggan_id" required><option value="">-- Pilih pelanggan --</option><?php foreach ($customers as $c): ?><option value="<?= e($c['id']) ?>"><?= e($c['id'].' — '.$c['nama']) ?></option><?php endforeach; ?></select></label>
  <label class="block sm:col-span-2">Keterangan<input class="w-full mt-2 border border-slate-300 px-3 py-2" type="text" name="keterangan" placeholder="Contoh: Pembelian alat tulis"></label>
  <div class="sm:col-span-2"><button type="submit" class="bg-slate-900 text-white px-5 py-3 font-bold text-xs uppercase">+ Simpan Transaksi</button></div>
 </form>
</section>
<section>
 <h2 class="font-serif text-xl font-extrabold uppercase border-b border-slate-200 pb-3 mb-4">Tabel Data Transaksi (Master)</h2>
 <div class="overflow-x-auto border border-slate-200"><table class="w-full text-xs text-left whitespace-nowrap"><thead class="bg-slate-50 uppercase text-slate-500"><tr><th class="p-3">ID</th><th class="p-3">Tanggal</th><th class="p-3">Pelanggan</th><th class="p-3">Keterangan</th><th class="p-3">Jenis Barang</th><th class="p-3">Total</th><th class="p-3">Aksi</th></tr></thead><tbody>
 <?php foreach ($transactions as $t): ?><tr class="border-t border-slate-100"><td class="p-3 font-bold">#<?= e($t['id']) ?></td><td class="p-3"><?= e($t['waktu_transaksi']) ?></td><td class="p-3"><?= e($t['nama_pelanggan']) ?></td><td class="p-3"><?= e($t['keterangan']) ?></td><td class="p-3"><?= e($t['jumlah_jenis']) ?></td><td class="p-3 font-semibold"><?= rupiah($t['total']) ?></td><td class="p-3"><a class="text-blue-700 font-bold underline" href="index.php?page=detail&amp;id=<?= e($t['id']) ?>">Lihat Detail</a></td></tr><?php endforeach; ?>
 <?php if (!$transactions): ?><tr><td colspan="7" class="p-5">Belum ada transaksi.</td></tr><?php endif; ?>
 </tbody></table></div>
</section>

<?php elseif ($page === 'detail'): ?>
<?php if (!$transaction): ?>
<p class="text-sm">Transaksi tidak ditemukan. <a class="text-blue-700 underline" href="index.php">Kembali ke transaksi</a></p>
<?php else: ?>
<section class="border border-slate-200 p-5 sm:p-7">
 <div class="flex flex-wrap items-center justify-between gap-3 mb-5"><h2 class="font-serif text-xl font-extrabold uppercase">Master: Transaksi #<?= e($transaction['id']) ?></h2><a class="border border-slate-300 px-3 py-2 text-xs font-bold" href="index.php?page=transaksi">Kembali ke Daftar</a></div>
 <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
  <div><span class="text-xs text-slate-400 uppercase block">Tanggal</span><strong><?= e($transaction['waktu_transaksi']) ?></strong></div>
  <div><span class="text-xs text-slate-400 uppercase block">Pelanggan</span><strong><?= e($transaction['nama_pelanggan']) ?></strong></div>
  <div><span class="text-xs text-slate-400 uppercase block">Keterangan</span><strong><?= e($transaction['keterangan']) ?></strong></div>
  <div><span class="text-xs text-slate-400 uppercase block">Total transaksi</span><strong class="text-xl"><?= rupiah($transaction['total']) ?></strong></div>
 </div>
</section>
<section>
 <h2 class="font-serif text-xl font-extrabold uppercase border-b border-slate-200 pb-3 mb-4">Detail Barang pada Transaksi Ini</h2>
 <div class="overflow-x-auto border border-slate-200"><table class="w-full text-xs text-left"><thead class="bg-slate-50 uppercase text-slate-500"><tr><th class="p-3">Barang</th><th class="p-3">Harga</th><th class="p-3">Qty</th><th class="p-3">Subtotal</th><th class="p-3">Aksi</th></tr></thead><tbody>
 <?php foreach ($details as $d): ?><tr class="border-t border-slate-100"><td class="p-3"><strong class="block"><?= e($d['nama_barang']) ?></strong><span class="text-slate-400"><?= e($d['kode_barang']) ?></span></td><td class="p-3"><?= rupiah($d['harga']) ?></td><td class="p-3"><?= e($d['qty']) ?></td><td class="p-3 font-bold"><?= rupiah($d['harga']*$d['qty']) ?></td><td class="p-3"><form method="post" onsubmit="return confirm('Hapus barang dari detail ini?')"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="hapus_detail"><input type="hidden" name="transaksi_id" value="<?= e($id) ?>"><input type="hidden" name="barang_id" value="<?= e($d['barang_id']) ?>"><button class="text-red-600 font-bold" type="submit">Hapus</button></form></td></tr><?php endforeach; ?>
 <?php if (!$details): ?><tr><td colspan="5" class="p-5">Belum ada detail barang.</td></tr><?php endif; ?>
 </tbody></table></div>
 <p class="mt-4 text-right font-bold">Total: <?= rupiah($transaction['total']) ?></p>
</section>
<section class="border border-slate-200 p-5 sm:p-7">
 <h2 class="font-serif text-xl font-extrabold uppercase mb-2">Tambah Detail Transaksi</h2>
 <p class="text-xs text-slate-500 mb-5">Barang yang sudah ada di transaksi ini otomatis tidak ditampilkan lagi pada dropdown.</p>
 <?php if ($available): ?>
 <form method="post" class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="tambah_detail"><input type="hidden" name="transaksi_id" value="<?= e($id) ?>">
 <label>Barang<select name="barang_id" class="w-full mt-2 border border-slate-300 bg-white px-3 py-2" required><option value="">-- Pilih barang --</option><?php foreach ($available as $b): ?><option value="<?= e($b['id']) ?>"><?= e($b['kode_barang'].' — '.$b['nama_barang']) ?> (<?= rupiah($b['harga']) ?>)</option><?php endforeach; ?></select></label>
 <label>Jumlah (Qty)<input class="w-full mt-2 border border-slate-300 px-3 py-2" type="number" min="1" max="100000" name="qty" value="1" required></label>
 <div class="sm:col-span-2"><button type="submit" class="bg-slate-900 text-white px-5 py-3 font-bold text-xs uppercase">+ Tambah Detail & Hitung Total</button></div></form>
 <?php else: ?><p class="text-sm">Semua barang sudah dimasukkan ke transaksi ini.</p><?php endif; ?>
</section>
<?php endif; ?>

<?php else: ?>
<section>
 <h2 class="font-serif text-xl font-extrabold uppercase border-b border-slate-200 pb-3 mb-4">Daftar Barang</h2>
 <p class="text-sm text-slate-600 mb-4">Barang yang sudah dipakai pada transaksi_detail tidak boleh dihapus.</p>
 <div class="overflow-x-auto border border-slate-200"><table class="w-full text-xs text-left whitespace-nowrap"><thead class="bg-slate-50 uppercase text-slate-500"><tr><th class="p-3">Kode</th><th class="p-3">Nama Barang</th><th class="p-3">Supplier</th><th class="p-3">Harga</th><th class="p-3">Stok</th><th class="p-3">Status</th><th class="p-3">Aksi</th></tr></thead><tbody>
 <?php foreach ($items as $b): ?><tr class="border-t border-slate-100"><td class="p-3"><?= e($b['kode_barang']) ?></td><td class="p-3 font-bold"><?= e($b['nama_barang']) ?></td><td class="p-3"><?= e($b['nama_supplier']) ?></td><td class="p-3"><?= rupiah($b['harga']) ?></td><td class="p-3"><?= e($b['stok']) ?></td><td class="p-3"><?= $b['digunakan'] ? 'Dipakai di '.$b['digunakan'].' detail' : 'Belum dipakai' ?></td><td class="p-3"><form method="post" onsubmit="return confirm('Yakin ingin menghapus barang ini?')"><input type="hidden" name="csrf" value="<?= e($_SESSION['csrf']) ?>"><input type="hidden" name="action" value="hapus_barang"><input type="hidden" name="barang_id" value="<?= e($b['id']) ?>"><button type="submit" class="text-red-600 font-bold">Hapus</button></form></td></tr><?php endforeach; ?>
 <?php if (!$items): ?><tr><td colspan="7" class="p-5">Belum ada barang.</td></tr><?php endif; ?>
 </tbody></table></div>
 <p class="text-xs text-slate-500 mt-4">Untuk menguji barang yang bisa dihapus, tambahkan barang baru melalui phpMyAdmin yang belum tercatat dalam transaksi_detail.</p>
</section>
<?php endif; ?>
</main>
<?php include __DIR__ . '/components/footer.php'; ?>
