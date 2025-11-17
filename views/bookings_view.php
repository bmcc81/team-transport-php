<?php
require_once __DIR__ . '/../services/config.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once "../includes/toast_helper.php";

// Fetch customers and trips for dropdowns
$customers = $pdo->query("SELECT id, customer_company_name FROM customers ORDER BY customer_company_name ASC")->fetchAll(PDO::FETCH_ASSOC);
$trips = $pdo->query("SELECT id, trip_name FROM trips ORDER BY trip_name ASC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch existing bookings
$stmt = $pdo->query("
    SELECT b.id, c.customer_company_name, t.trip_name, b.booking_date, b.status
    FROM bookings b
    JOIN customers c ON b.customer_id = c.id
    JOIN trips t ON b.trip_id = t.id
    ORDER BY b.booking_date DESC
");
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Bookings</title>
  <link href="../styles/css/bootstrap.min.css" rel="stylesheet">
  <link href="../styles/shared.css" rel="stylesheet">
</head>
<body class="p-4 bg-light">
  <a href="../dashboard.php" class="btn btn-secondary mb-3">← Back to Dashboard</a>
  <h2 class="mb-4">Manage Bookings</h2>

  <!-- Booking Creation Form -->
  <form method="POST" action="../includes/create_booking.php" class="card p-4 shadow-sm mb-4">
    <div class="row g-3">
      <div class="col-md-5">
        <label class="form-label">Customer *</label>
        <select class="form-select" name="customer_id" required>
          <option value="">Select a customer</option>
          <?php foreach ($customers as $c): ?>
            <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['customer_company_name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-5">
        <label class="form-label">Trip *</label>
        <select class="form-select" name="trip_id" required>
          <option value="">Select a trip</option>
          <?php foreach ($trips as $t): ?>
            <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['trip_name']); ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="col-md-2 d-flex align-items-end">
        <button type="submit" class="btn btn-success w-100">Book</button>
      </div>
    </div>
  </form>

  <!-- Bookings Table -->
  <div class="card shadow-sm">
    <div class="card-body">
      <h5 class="card-title mb-3">All Bookings</h5>
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Customer</th>
            <th>Trip</th>
            <th>Booking Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($bookings)): ?>
            <?php foreach ($bookings as $b): ?>
              <tr>
                <td><?= htmlspecialchars($b['customer_company_name']); ?></td>
                <td><?= htmlspecialchars($b['trip_name']); ?></td>
                <td><?= htmlspecialchars($b['booking_date']); ?></td>
                <td>
                  <span class="badge bg-<?=
                    match($b['status']) {
                      'confirmed' => 'success',
                      'completed' => 'primary',
                      'cancelled' => 'danger',
                      default => 'secondary'
                    }
                  ?>">
                    <?= ucfirst($b['status']); ?>
                  </span>
                </td>
                <td class="text-nowrap">
                  <form method="POST" action="../includes/update_booking_status.php" class="d-inline">
                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                    <select name="status" class="form-select form-select-sm d-inline w-auto" onchange="this.form.submit()">
                      <option <?= $b['status']=='pending'?'selected':'' ?> value="pending">Pending</option>
                      <option <?= $b['status']=='confirmed'?'selected':'' ?> value="confirmed">Confirmed</option>
                      <option <?= $b['status']=='completed'?'selected':'' ?> value="completed">Completed</option>
                      <option <?= $b['status']=='cancelled'?'selected':'' ?> value="cancelled">Cancelled</option>
                    </select>
                  </form>

                  <form method="POST" action="../includes/delete_booking.php" class="d-inline" onsubmit="return confirm('Delete this booking?');">
                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                    <button class="btn btn-danger btn-sm">Delete</button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr><td colspan="5" class="text-center text-muted">No bookings found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Toasts -->
  <?php include "../includes/toasts.php"; ?>
  <script src="../styles/js/bootstrap.bundle.min.js"></script>
</body>
</html>
