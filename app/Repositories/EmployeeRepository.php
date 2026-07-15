<?php

namespace App\Repositories;

use App\Models\Employee;

class EmployeeRepository
{
    public function create(array $data): Employee
    {
        return Employee::create($data);
    }

    public function getAll()
    {
        // GAP-COMPANY: company_id scoping will be enforced once auth/tenant middleware is wired
        return Employee::query()->latest()->paginate(15);
    }
}
