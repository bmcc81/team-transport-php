<?php $pageTitle = "Manage Users"; require __DIR__ . '/../../layout/header.php'; ?>

<div class="container mt-4">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4">Users</h2>
        <a href="/admin/users/create" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Add User
        </a>
    </div>

    <div class="table-responsive card shadow-sm">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Email</th>
                    <th>Created</th>
                    <th style="width:120px;">Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['full_name']) ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td><span class="badge bg-secondary"><?= $u['role'] ?></span></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= date("M j, Y", strtotime($u['created_at'])) ?></td>
                        <td>
                            <a href="/admin/users/edit/<?= $u['id'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="/admin/users/delete/<?= $u['id'] ?>" class="d-inline">
                                <button class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user?')">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>

        </table>
    </div>

</div>

<?php require __DIR__ . '/../../layout/footer.php'; ?>
