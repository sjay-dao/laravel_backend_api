<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RMSControllerV2 extends Controller
{
    public function index(Request $request)
    {
        $limit = $request->get('limit', 500);

       $records = DB::connection('ers')
        ->table('tblformitrequestv3 as a')
        ->leftJoin('tblstatus as t', function ($join) {
            $join->on('a.CurrentStatusId', '=', 't.id')
                ->where('t.formId', 130);
        })
        ->leftJoin('tbl_tmp_ems_org_group as c', 'a.Section', '=', 'c.id')
        ->leftJoin('tbl_globalreference as d', function ($join) {
            $join->on('a.TypeId', '=', 'd.grid')
                ->where('d.grgid', 49);
        })
        ->leftJoin('tblcategory as e', 'a.CategoryId', '=', 'e.id')
        ->leftJoin('tblsubcategory as f', 'a.SubCategoryId', '=', 'f.id')
        ->leftJoin('tbl_globalreference as g', function ($join) {
            $join->on('a.SeverityId', '=', 'g.grid')
                ->where('g.grgid', 52);
        })
        ->leftJoin('tbl_globalreference as h', function ($join) {
            $join->on('a.PriorityId', '=', 'h.grid')
                ->where('h.grgid', 53);
        })
        ->select([
            'a.id',
            DB::raw('a.CreatedDate AS RequestDate'),
            DB::raw('a.Requestor AS requestor'),
            'a.assign_user',
            DB::raw('UPPER(a.Title) AS title'),
            DB::raw('UPPER(c.description) AS section'),
            DB::raw('UPPER(d.referencename) AS type'),
            DB::raw('UPPER(e.categoryname) AS category'),
            DB::raw('UPPER(f.subcategoryname) AS subcategory'),
            DB::raw('UPPER(g.referencename) AS severity'),
            DB::raw('UPPER(h.referencename) AS priority'),
            'a.ResolvedDate',
            'a.ClosedDate',
            DB::raw('a.Branch AS Branch'),
            'a.UpdatedDate',
            'a.CurrentStatusId',
            't.status',
            't.Tag',
            'a.UpdatedBy',
            'a.LastComment',
            'a.LastCommentDate',
            'a.ActualTAT',
            'a.TargetTAT',
            'a.CreatedDate',
        ])
        ->where('a.deletedflag', 0)
        ->whereRaw("UPPER(e.categoryname) = 'RMS'")
        ->whereRaw("UPPER(c.description) LIKE '%SOFTWARE SECTION%'")
        ->where('t.Tag', 'OPEN')
        // ->whereNotIn('t.status', ['RESOLVED'])
        ->orderByDesc('a.CreatedDate')
        ->paginate($limit);

        $codes = collect($records->items())
        ->flatMap(function ($item) {
            return [
                $item->UpdatedBy,
                $item->assign_user,
                $item->requestor
            ];
        })
        ->filter()
        ->unique()
        ->values()
        ->toArray();

        $employees = DB::connection('ems_plantilla')
            ->table('employee')
            ->select(
                'code',
                DB::raw("CONCAT(firstname, ' ', lastname) AS employee_name")
            )
            ->whereIn('code', $codes)
            ->get()
            ->keyBy('code');

        $records->getCollection()->transform(function ($row) use ($employees) {

            $updatedBy = $employees->get($row->UpdatedBy);
            $resolver = $employees->get($row->assign_user);
            $requestor = $employees->get($row->requestor);

            $row->UpdatedBy = $updatedBy
                ? $row->UpdatedBy . '-' . $updatedBy->employee_name
                : $row->UpdatedBy;

            $row->resolver = $resolver
                ? $row->assign_user . '-' . $resolver->employee_name
                : $row->assign_user;

            unset($row->assign_user);

            $row->requestor = $requestor
                ? $row->requestor . '-' . $requestor->employee_name
                : $row->requestor;
            
            return $row;
    });

        return response()->json($records);
    }
}