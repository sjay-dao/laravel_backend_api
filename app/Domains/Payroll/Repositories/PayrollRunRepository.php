<?php
namespace App\Domains\Payroll\Repositories;

use App\Domains\Payroll\Models\PayrollRun;

class PayrollRunRepository
{
    public function paginate()
    {
        return PayrollRun::latest()->paginate();
    }

    public function find(int $id)
    {
        return PayrollRun::with('details.employee')
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        return PayrollRun::create($data);
    }

    public function update(PayrollRun $run, array $data)
    {
        $run->update($data);

        return $run->refresh();
    }

    public function delete(PayrollRun $run)
    {
        return $run->delete();
    }
}