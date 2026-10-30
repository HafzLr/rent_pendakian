<?php
// admin/users.php - User Identity Verification Queue (FR-36)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/auth.php';
require_once __DIR__ . '/../helpers/functions.php';

requireAdmin();
$db = getDB();

// Handle Actions (Approve / Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $targetUserId = (int)($_POST['user_id'] ?? 0);
    $csrf = $_POST['csrf_token'] ?? '';

    if (!validateCsrfToken($csrf)) {
        setFlash('error', 'Sesi tidak valid.');
    } elseif ($targetUserId > 0) {
        if ($action === 'approve') {
            $stmt = $db->prepare("UPDATE users SET verification_status = 'verified', verification_notes = NULL WHERE user_id = ?");
            $stmt->execute([$targetUserId]);
            setFlash('success', 'Pengguna #' . $targetUserId . ' berhasil diverifikasi.');
        } elseif ($action === 'reject') {
            $notes = trim($_POST['verification_notes'] ?? '');
            if (empty($notes)) {
                setFlash('error', 'Alasan penolakan wajib diisi untuk memberi tahu penyewa.');
            } else {
                $stmt = $db->prepare("UPDATE users SET verification_status = 'rejected', verification_notes = ? WHERE user_id = ?");
                $stmt->execute([$notes, $targetUserId]);
                setFlash('warning', 'Pengguna #' . $targetUserId . ' ditolak dengan catatan: ' . htmlspecialchars($notes));
            }
        }
    }
    header('Location: ' . baseUrl('admin/users.php'));
    exit;
}

$statusFilter = $_GET['status'] ?? 'pending';

$sql = "SELECT * FROM users WHERE role = 'user'";
$params = [];

if ($statusFilter !== 'all') {
    $sql .= " AND verification_status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY (verification_status = 'pending') DESC, user_id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll();

$adminTitle = 'Verifikasi KTP Pengguna - Admin RentGear';
include __DIR__ . '/includes/admin_header.php';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-3 mb-4 border-bottom">
  <div>
    <h1 class="h3 font-heading mb-1">Verifikasi Identitas Pengguna (KTP/SIM)</h1>
    <p class="text-muted small mb-0">Tinjau kesesuaian dokumen kartu identitas sebelum penyewa dapat membuat pesanan sewa.</p>
  </div>
</div>

<!-- Status Filters -->
<div class="d-flex gap-2 mb-4">
  <a href="<?= baseUrl('admin/users.php?status=pending') ?>" class="btn btn-sm <?= $statusFilter === 'pending' ? 'btn-warning fw-bold' : 'btn-outline-secondary' ?>">
    <i class="fas fa-clock me-1"></i> Menunggu Verifikasi (Pending)
  </a>
  <a href="<?= baseUrl('admin/users.php?status=verified') ?>" class="btn btn-sm <?= $statusFilter === 'verified' ? 'btn-success fw-bold' : 'btn-outline-secondary' ?>">
    <i class="fas fa-check-circle me-1"></i> Terverifikasi
  </a>
  <a href="<?= baseUrl('admin/users.php?status=rejected') ?>" class="btn btn-sm <?= $statusFilter === 'rejected' ? 'btn-danger fw-bold' : 'btn-outline-secondary' ?>">
    <i class="fas fa-times-circle me-1"></i> Ditolak
  </a>
  <a href="<?= baseUrl('admin/users.php?status=all') ?>" class="btn btn-sm <?= $statusFilter === 'all' ? 'btn-dark fw-bold' : 'btn-outline-secondary' ?>">
    Semua Pengguna
  </a>
</div>

<!-- Users List -->
<?php if (empty($users)): ?>
  <div class="lk-card p-5 text-center text-muted">
    <i class="fas fa-users-slash fa-3x mb-3 text-secondary"></i>
    <h5 class="font-heading">Tidak Ada Pengguna dalam Kategori Ini</h5>
    <p class="small mb-0">Semua verifikasi telah ditangani.</p>
  </div>
<?php else: ?>
  <div class="lk-card p-4">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead class="table-light small text-muted">
          <tr>
            <th>ID</th>
            <th>Penyewa</th>
            <th>Kontak (WA)</th>
            <th>Dokumen KTP/SIM</th>
            <th>Status</th>
            <th>Terdaftar</th>
            <th class="text-end">Aksi Verifikasi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $u): ?>
            <tr id="user-<?= $u['user_id'] ?>">
              <td><strong>#<?= $u['user_id'] ?></strong></td>
              <td>
                <div class="fw-bold"><?= htmlspecialchars($u['name']) ?></div>
                <div class="small text-muted"><?= htmlspecialchars($u['email']) ?></div>
                <?php if (!empty($u['verification_notes'])): ?>
                  <div class="small text-danger mt-1">
                    <strong>Catatan Ditolak:</strong> <?= htmlspecialchars($u['verification_notes']) ?>
                  </div>
                <?php endif; ?>
              </td>
              <td>
                <a href="<?= generateWhatsAppUrl($u['user_number'], "Halo {$u['name']}, kami dari admin RentGear...") ?>" target="_blank" class="text-success text-decoration-none">
                  <i class="fab fa-whatsapp me-1"></i> <?= htmlspecialchars($u['user_number']) ?>
                </a>
              </td>
              <td>
                <?php if (!empty($u['idcard_photo'])): ?>
                  <button type="button" class="btn btn-sm btn-outline-forest py-1 px-2" data-bs-toggle="modal" data-bs-target="#ktpModal-<?= $u['user_id'] ?>">
                    <i class="fas fa-image me-1"></i> Pratinjau KTP
                  </button>

                  <!-- Lightbox Modal -->
                  <div class="modal fade" id="ktpModal-<?= $u['user_id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                      <div class="modal-content">
                        <div class="modal-header">
                          <h5 class="modal-title font-heading fs-6">KTP/SIM: <?= htmlspecialchars($u['name']) ?> (#<?= $u['user_id'] ?>)</h5>
                          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body text-center p-3">
                          <img src="<?= baseUrl('api/download-doc.php?type=idcard&id=' . $u['user_id']) ?>" alt="KTP" class="img-fluid rounded shadow-sm" style="max-height: 500px;">
                        </div>
                        <div class="modal-footer">
                          <a href="<?= baseUrl('api/download-doc.php?type=idcard&id=' . $u['user_id']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-external-link-alt me-1"></i> Buka Gambar Penuh
                          </a>
                          <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Tutup</button>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php else: ?>
                  <span class="badge bg-secondary">Belum Upload</span>
                <?php endif; ?>
              </td>
              <td><?= getUserVerificationBadge($u['verification_status']) ?></td>
              <td class="small text-muted"><?= formatDate($u['created_at']) ?></td>
              <td class="text-end">
                <div class="d-inline-flex gap-1">
                  <!-- Approve Button -->
                  <form action="<?= baseUrl('admin/users.php') ?>" method="POST" onsubmit="return confirm('Verifikasi identitas <?= htmlspecialchars($u['name']) ?>?')">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <input type="hidden" name="action" value="approve">
                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                    <button type="submit" class="btn btn-sm btn-success py-1 px-2" title="Setujui Verifikasi">
                      <i class="fas fa-check"></i> Setujui
                    </button>
                  </form>

                  <!-- Reject Modal Trigger Button -->
                  <button type="button" class="btn btn-sm btn-outline-danger py-1 px-2" data-bs-toggle="modal" data-bs-target="#rejectModal-<?= $u['user_id'] ?>" title="Tolak Verifikasi">
                    <i class="fas fa-times"></i> Tolak
                  </button>

                  <!-- Reject Reason Modal -->
                  <div class="modal fade" id="rejectModal-<?= $u['user_id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                      <div class="modal-content text-start">
                        <form action="<?= baseUrl('admin/users.php') ?>" method="POST">
                          <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                          <input type="hidden" name="action" value="reject">
                          <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">

                          <div class="modal-header">
                            <h5 class="modal-title font-heading fs-6">Tolak Verifikasi: <?= htmlspecialchars($u['name']) ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                          </div>
                          <div class="modal-body">
                            <label class="form-label small fw-bold">Alasan Penolakan <span class="text-danger">*</span></label>
                            <textarea name="verification_notes" class="form-control" rows="3" placeholder="Contoh: Foto KTP buram, tidak terbaca, atau nama akun berbeda dengan KTP." required></textarea>
                            <div class="form-text small">Penyewa akan dapat membaca pesan ini di profil mereka dan mengunggah ulang KTP baru.</div>
                          </div>
                          <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-danger btn-sm">Konfirmasi Penolakan</button>
                          </div>
                        </form>
                      </div>
                    </div>
                  </div>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<?php include __DIR__ . '/includes/admin_footer.php'; ?>
