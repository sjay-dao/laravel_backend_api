<?php

namespace App\Domains\Reference\Controllers;

use App\Domains\Reference\Models\Branch;
use App\Domains\Reference\Requests\StoreBranchRequest;
use App\Domains\Reference\Requests\UpdateBranchRequest;
use App\Domains\Reference\Resources\BranchResource;
use App\Domains\Reference\Services\BranchService;
use App\Domains\Shared\Controllers\BaseApiController;
use Illuminate\Http\Request;

class BranchController extends BaseApiController
{
    public function __construct(
        protected BranchService $service
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizeAbility($request, 'reference.branches.view');

        $branches = $this->service->paginate(
            $request->integer('per_page', 15),
            $request->search
        );

        return $this->paginated(
            $branches,
            BranchResource::class,
            'Branches retrieved successfully.'
        );
    }

    public function show(Request $request, Branch $branch)
    {
        $this->authorizeAbility($request, 'reference.branches.view');

        return $this->resource(
            new BranchResource(
                $branch->load(
                    'barangay.cityMun.province.region'
                )
            )
        );
    }

    public function store(StoreBranchRequest $request)
    {
        $this->authorizeAbility(
            $request,
            'reference.branches.create'
        );

        $branch = $this->service->create(
            $request->validated()
        );

        return $this->created(
            new BranchResource($branch)
        );
    }

    public function update(
        UpdateBranchRequest $request,
        Branch $branch
    ) {
        $this->authorizeAbility(
            $request,
            'reference.branches.update'
        );

        $branch = $this->service->update(
            $branch,
            $request->validated()
        );

        return $this->resource(
            new BranchResource($branch)
        );
    }

    public function destroy(
        Request $request,
        Branch $branch
    ) {
        $this->authorizeAbility(
            $request,
            'reference.branches.delete'
        );

        $this->service->delete($branch);

        return $this->deleted();
    }

    public function options(Request $request)
    {
        $this->authorizeAbility(
            $request,
            'reference.branches.view'
        );

        return $this->success(
            $this->service->options()
        );
    }
}