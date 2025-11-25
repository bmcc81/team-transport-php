<?php
require_once "../includes/admin_protect.php"; // ensures only admins can access
include __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../services/config.php';

try {
    // Fetch all users with creator info
    $stmt = $pdo->query("
        SELECT 
            u.id,
            u.username,
            u.email,
            u.role,
            a.username AS created_by_admin,
            u.created_at
        FROM users u
        LEFT JOIN users a ON u.created_by = a.id
        ORDER BY u.created_at DESC
    ");

    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - Admin</title>
</head>
<body>
<h2 class="mb-4">User Management</h2>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?= htmlspecialchars($_SESSION['success']); ?></div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['error']); ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<table class="table table-striped table-hover shadow-sm">
    <thead class="table-dark table-theme">
        <tr>
            <th>ID</th>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th>Created By</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($users): ?>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= htmlspecialchars($user['id']); ?></td>
                    <td><?= htmlspecialchars($user['username']); ?></td>
                    <td><?= htmlspecialchars($user['email']); ?></td>
                    <td>
                        <span class="badge bg-<?php
                            echo $user['role'] === 'admin' ? 'danger' :
                                 ($user['role'] === 'driver' ? 'primary' :
                                 ($user['role'] === 'dispatcher' ? 'warning text-dark' : 'secondary'));
                        ?>">
                            <?= htmlspecialchars(ucfirst($user['role'])); ?>
                        </span>
                    </td>
                    <td><?= htmlspecialchars($user['created_by_admin'] ?? '—'); ?></td>
                    <td><?= htmlspecialchars($user['created_at'] ?? '—'); ?></td>
                    <td>
                        <?php if ($user['username'] !== 'admin'): ?>
                            <div class="row">
                                <div class="class col-6">
                                    <!-- Delete Button triggers Modal -->
                                    <button type="button" class="btn btn-sm btn-danger col-12" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#deleteModal<?= $user['id']; ?>">
                                        Delete
                                    </button>
                                </div>
                                <div class="class col-6">
                                    <!-- Edit Button -->
                                    <form method="GET" action="../views/edit_user_view.php" style="display:inline;">
                                            <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']); ?>">
                                            <button type="submit" class="btn btn-sm btn-primary col-12">Edit</button>
                                    </form>

                                </div>

                            </div>
                            
             
                            <!-- Delete Confirmation Modal -->
                            <div class="modal fade" id="deleteModal<?= $user['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $user['id']; ?>" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header bg-danger text-white">
                                            <h5 class="modal-title" id="deleteModalLabel<?= $user['id']; ?>">Confirm Delete</h5>
                                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            Are you sure you want to permanently delete user 
                                            <strong><?= htmlspecialchars($user['username']); ?></strong>?
                                            <p class="text-muted mt-2 mb-0"><small>This action cannot be undone.</small></p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <form method="POST" action="../includes/delete_user.php">
                                                <input type="hidden" name="id" value="<?= htmlspecialchars($user['id']); ?>">
                                                <button type="submit" class="btn btn-danger">Confirm Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">Protected</span>
                        <?php endif; ?>
                    </td>

                </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="7" class="text-center">No users found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

</body>
</html>
