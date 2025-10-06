<?php
function validateCustomerData(array $data): array {
    $errors = [];

    // Required fields
    if (empty(trim($data['customer_company_name'] ?? ''))) {
        $errors[] = "Company name is required.";
    }
    if (empty(trim($data['customer_contact_first_name'] ?? ''))) {
        $errors[] = "First name is required.";
    }
    if (empty(trim($data['customer_contact_last_name'] ?? ''))) {
        $errors[] = "Last name is required.";
    }
    if (empty(trim($data['customer_email'] ?? ''))) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($data['customer_email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Optional but validate format if provided
    if (!empty($data['customer_website']) && !filter_var($data['customer_website'], FILTER_VALIDATE_URL)) {
        $errors[] = "Invalid website URL.";
    }

    if (!empty($data['customer_phone']) && !preg_match('/^\+?[0-9\-\s]+$/', $data['customer_phone'])) {
        $errors[] = "Invalid phone number.";
    }

    return $errors;
}
