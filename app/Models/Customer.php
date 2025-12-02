<?php
namespace App\Models;

use App\Database\Database;

class Customer
{
    public static function all(): array
    {
        $pdo = Database::pdo();
        $stmt = $pdo->query("SELECT id, customer_company_name FROM customers ORDER BY customer_company_name");
        return $stmt->fetchAll() ?: [];
    }
}
