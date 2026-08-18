<?php
namespace App\Domains\Reference\Repositories;
use App\Domains\Reference\Models\Customer;
use App\Domains\Shared\Repositories\BaseRepository;
class CustomerRepository extends BaseRepository
{
    protected array $with = [
        'barangay.cityMun.province.region',
    ];
    public function __construct(Customer $model)
    {
        $this->model = $model;
    }
    public function paginate(
        int $perPage = 15,
        ?string $search = null
    ) {
        return $this->query()
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('contact_number', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->paginate($perPage);
    }
    public function options()
    {
        return $this->model
            ->select('id', 'code', 'name')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }
}