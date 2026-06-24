<?php

namespace App\Services\Rms;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;


const connectionName = 'ers';
const default_form_id = 130;
class RmsProjectQueryService
{
    public function getForms()
    {
        // logger()->info('ERS connection check', [
        //     'connection' => DB::connection(connectionName)->getName(),
        //     'database' => DB::connection(connectionName)->getDatabaseName(),
        //     'host' => config('database.connections.mysql.host'),
        // ]);
        return DB::connection(connectionName)
            ->table('tblform')
            ->select('id', 'TableName')
            ->orderBy('TableName')
            ->get();
    }

    public function getColumnsByFormId($formId = default_form_id)
    {
        return DB::connection(connectionName)
        ->table('tblcolumnlist')
        ->select(
            'id',
            'FormId',
            'Title',
            'ColumnName',
        )
        ->where('FormId', $formId)
        ->orderByRaw('CAST(Sequence AS SIGNED) ASC')
        ->get();
    }

    public function getPaginatedProjects(Request $request, int $formId = default_form_id)
    {
        logger()->info('Getting paginated projects', ['formId' => $formId]);

        $formId = $request->input('formId', default_form_id);
        $limit = $request->input('limit', 10);

        $records = $this->baseQuery($formId)
            ->orderByDesc('a.id')
            ->paginate($limit);

        $columns = $this->getColumnsByFormId($formId);

        return [
            'columns' => $columns,
            'records' => $records,
        ];
    }

    /* private function baseQuery()
     {
         return DB::connection('ers')
             ->table('tblFormITProject as a')
             ->leftJoin('tblstatus as s', function ($join) {
                 $join->on('s.id', '=', 'a.CurrentStatusId')
                     ->where('s.formid', default_form_id);
             })
             ->leftJoin('tbl_globalreference as tgr', 'a.AdditionalTypeId', '=', 'tgr.grid')
             ->leftJoin('tblcategory as c', function ($join) {
                 $join->on('c.id', '=', 'a.CategoryId')
                     ->where('c.formid', default_form_id);
             })
             ->select(
                 'a.id',
                 'a.CurrentStatusId',
                 'a.Title',
                 's.status',
                 'a.CreatedDate',
                 DB::raw('a.CreatedBy as emp_fullname'),
                 'a.UpdatedBy',
                 'a.UpdatedDate',
                 'a.ScopeOfRequirements',
                 'a.ProjectPriority',
                 'a.TeamMembers',
                 'a.Requestor',
                 'tgr.referencename',
                 DB::raw('a.LastComment as comment'),
                 DB::raw('a.LastCommentDate as createdt'),
                 's.Tag',
                 DB::raw('c.categoryname as CategoryName'),
                 'a.CategoryId',
                 DB::raw('a.Department as RespondentOrgGroupId'),
                 'a.HasAttachment',
                 'a.Progress',
                 'a.ActionDone',
                 'a.PlannedStartDate',
                 'a.PlannedEndDate'
             )
             ->where('a.deletedflag', 0);
     }
    */
    private function baseQuery(int $formId = default_form_id)
    {
        $form = DB::connection(connectionName)
            ->table('tblform')
            ->select('TableName')
            ->where('id', $formId)
            ->first();

        if (!$form || empty($form->TableName) || $form->TableName === '0') {
            throw new \Exception("No valid table found for form id {$formId}");
        }

        return DB::connection(connectionName)
            ->table($form->TableName . ' as a')
            ->leftJoin('tblstatus as s', function ($join) use ($formId) {
                $join->on('s.id', '=', 'a.CurrentStatusId')
                    ->where('s.formid', $formId);
            })
            ->select(
                'a.*',
                's.status as Status'
            );
    }
    
    private function applyFilters($query, Request $request): void
    {
        $this->applySearchFilter($query, $request);
        $this->applyExactFilters($query, $request);
        $this->applyDateFilters($query, $request);
    }

    private function applySearchFilter($query, Request $request): void
    {
        if (!$request->filled('search')) {
            return;
        }

        $search = $request->input('search');

        $query->where(function ($q) use ($search) {
            $q->where('a.Title', 'like', "%{$search}%")
                ->orWhere('a.Requestor', 'like', "%{$search}%")
                ->orWhere('a.CreatedBy', 'like', "%{$search}%")
                ->orWhere('a.LastComment', 'like', "%{$search}%");
        });
    }

    private function applyExactFilters($query, Request $request): void
    {
        $filters = [
            'status' => 's.status',
            'section' => 'tgr.referencename',
            'category' => 'c.categoryname',
            'priority' => 'a.ProjectPriority',
            'has_attachment' => 'a.HasAttachment',
        ];

        foreach ($filters as $requestKey => $column) {
            if ($request->filled($requestKey)) {
                $query->where($column, $request->input($requestKey));
            }
        }
    }

    private function applyDateFilters($query, Request $request): void
    {
        if ($request->filled('date_from')) {
            $query->whereDate('a.CreatedDate', '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate('a.CreatedDate', '<=', $request->input('date_to'));
        }
    }
    
}