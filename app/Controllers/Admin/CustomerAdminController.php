<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Customer;

class CustomerAdminController extends Controller
{
    public function index(): void
    {
        $customers = Customer::all();
        $this->view('admin/customers/index', compact('customers'));
    }

    public function create(): void
    {
        $this->view('admin/customers/create');
    }

    public function store(): void
    {
        $data = [
            'company'    => $_POST['company'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'last_name'  => $_POST['last_name'] ?? '',
            'phone'      => $_POST['phone'] ?? '',
            'email'      => $_POST['email'] ?? '',
            'address'    => $_POST['address'] ?? '',
            'city'       => $_POST['city'] ?? '',
            'postal'     => $_POST['postal'] ?? '',
            'notes'      => $_POST['notes'] ?? ''
        ];

        Customer::create($data, $_SESSION['user_id']);
        header("Location: /admin/customers");
        exit;
    }

    public function edit($id): void
    {
        $customer = Customer::find((int)$id);
        if (!$customer) {
            http_response_code(404);
            echo "Customer not found";
            return;
        }
        $this->view('admin/customers/edit', compact('customer'));
    }

    public function update($id): void
    {
        $data = [
            'company'    => $_POST['company'] ?? '',
            'first_name' => $_POST['first_name'] ?? '',
            'last_name'  => $_POST['last_name'] ?? '',
            'phone'      => $_POST['phone'] ?? '',
            'email'      => $_POST['email'] ?? '',
            'address'    => $_POST['address'] ?? '',
            'city'       => $_POST['city'] ?? '',
            'postal'     => $_POST['postal'] ?? '',
            'notes'      => $_POST['notes'] ?? ''
        ];

        Customer::update((int)$id, $data);
        header("Location: /admin/customers");
        exit;
    }

    public function delete($id): void
    {
        Customer::delete((int)$id);
        header("Location: /admin/customers");
        exit;
    }
}
