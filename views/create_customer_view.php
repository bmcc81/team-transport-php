<!-- INDEX.php -->
<?php session_start(); 
echo $_SESSION['username']; // outputs the logged-in username ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Create Customer</title>
  <link href="../styles/css/bootstrap.min.css" rel="stylesheet">
    <link href="../styles/shared.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center">
  <div class="card shadow-lg p-4" style="max-width: 400px; width: 100%;">
    <h3 class="text-center mb-4">Create Customer Form</h3>
    
    <?php if (isset($_SESSION['error'])): ?>
      <div class="alert alert-danger">
        <?= $_SESSION['error']; unset($_SESSION['error']); ?>
      </div>
    <?php endif; ?>

    <form method="POST" action="../includes/create_customer.php">
        <div class="mb-3">
            <label for="customer_company_name" class="form-label">Customer Company Name <div class="required">(Required)</div></label>
            <input type="text" class="form-control" id="customer_company_name" name="customer_company_name" required>
        </div>

        <div class="mb-3">
            <label for="customer_internal_handler_name" class="form-label">Customer Internal Handler Name</label>
            <input type="text" class="form-control" id="customer_internal_handler_name" name="customer_internal_handler_name" required>
        </div>  

        <div class="mb-3">
            <label for="customer_contact_first_name" class="form-label">Customer Contact First Name <div class="required">(Required)</div></label>
            <input type="text" class="form-control" id="customer_contact_first_name" name="customer_contact_first_name" required>
        </div>

        <div class="mb-3">
            <label for="customer_contact_last_name" class="form-label">Customer Contact Last Name <div class="required">(Required)</div></label>
            <input type="text" class="form-control" id="customer_contact_last_name" name="customer_contact_last_name" required>
        </div>

        <div class="mb-3">
            <label for="customer_email" class="form-label">Customer Email Address <div class="required">(Required)</div></label>
            <input type="email" class="form-control" id="customer_email" name="customer_email" required>
        </div>

        <div class="mb-3">
            <label for="customer_contact_address" class="form-label">Customer Contact Address <div class="required">(Required)</div></label>
            <input type="text" class="form-control" id="customer_contact_address" name="customer_contact_address" required>
        </div>

        <div class="mb-3">
            <label for="customer_contact_city" class="form-label">Customer Contact City <div class="required">(Required)</div></label>
            <input type="text" class="form-control" id="customer_contact_city" name="customer_contact_city" required>
        </div>

        <div class="mb-3">
            <label for="customer_contact_state_or_province" class="form-label">Customer Contact State or Province <div class="required">(Required)</div></label>
            <input type="text" class="form-control" id="customer_contact_state_or_province" name="customer_contact_state_or_province" required>
        </div>

        <div class="mb-3">
            <label for="customer_contact_zip_or_postal_code" class="form-label">Customer Contact Zip or Postal Code <div class="required">(Required)</div></label>
            <input type="text" class="form-control" id="customer_contact_zip_or_postal_code" name="customer_contact_zip_or_postal_code" required>
        </div>

        <div class="mb-3">
            <label for="customer_contact_country" class="form-label">Customer Contact Country <div class="required">(Required)</div></label>
            <input type="text" class="form-control" id="customer_contact_country" name="customer_contact_country" required>
        </div>

        <div class="mb-3">
            <label for="customer_phone" class="form-label">Customer Contact Phone Number <div class="required">(Required)</div></label>
            <input type="text" class="form-control" id="customer_phone" name="customer_phone" required>
        </div>

        <div class="mb-3">
            <label for="customer_fax" class="form-label">Customer Contact Fax Number</label>
            <input type="text" class="form-control" id="customer_fax" name="customer_fax">
        </div>

        <div class="mb-3">
            <label for="customer_website" class="form-label">Customer Web Site</label>
            <input type="text" class="form-control" id="customer_website" name="customer_website">
        </div>

        <button type="submit" class="btn btn-primary w-100">Create Customer</button>
    </form>
  </div>
</div>

</body>
</html>
