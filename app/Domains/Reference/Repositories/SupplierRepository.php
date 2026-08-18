<?php
namespace App\Domains\Reference\Repositories;
use App\Domains\Reference\Models\Supplier;
use App\Domains\Shared\Repositories\BaseRepository;
class SupplierRepository extends BaseRepository
{
    protected array $with = [
        'barangay.cityMun.province.region',
    ];
    public function __construct(Supplier $model)
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
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('tin', 'like', "%{$search}%")
                        ->orWhereHas('barangay', function ($q) use ($search) {
                            $q->where('description', 'like', "%{$search}%");
                        })
                        ->orWhereHas('barangay.cityMun', function ($q) use ($search) {
                            $q->where('description', 'like', "%{$search}%");
                        })
                        ->orWhereHas('barangay.cityMun.province', function ($q) use ($search) {
                            $q->where('description', 'like', "%{$search}%");
                        });
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