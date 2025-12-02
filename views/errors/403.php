<?php 
$pageTitle = "Access Denied"; 
require __DIR__ . '/../layout/header.php'; 
?>

<div class="container mt-5">
    <div class="text-center">
        <h1 class="display-5 text-danger">
            <i class="bi bi-shield-lock"></i> 403 Forbidden
        </h1>
        <p class="lead mt-3">
            You do not have permission to access this page.
        </p>
        <a href="/dashboard" class="btn btn-primary mt-4">
            Return to Dashboard
        </a>
    </div>
</div>

<?php require __DIR__ . '/../layout/footer.php'; ?>
