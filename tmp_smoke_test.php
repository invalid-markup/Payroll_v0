<?php

use App\Models\Employee;
use App\Models\Loan;
use App\Models\PayrollRun;
use App\Models\User;

$u = User::where('email', 'admin@payeasy.com')->first();
echo 'company_id : '.($u->company_id ?? 'NULL').PHP_EOL;
$cid = $u->company_id;
echo 'employees  : '.Employee::where('company_id', $cid)->where('status', 'ACTIVE')->count().PHP_EOL;
echo 'loans      : '.Loan::whereHas('employee', fn ($q) => $q->where('company_id', $cid))->where('loan_status', 'ACTIVE')->count().PHP_EOL;
echo 'runs       : '.PayrollRun::where('company_id', $cid)->count().PHP_EOL;
echo 'OK'.PHP_EOL;
