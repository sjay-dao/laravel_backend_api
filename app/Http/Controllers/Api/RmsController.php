<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\RmsNewRequestMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Services\Rms\RmsProjectQueryService;


const default_form_id = 130;
class RmsController extends Controller
{
    protected RmsProjectQueryService $rmsProjectQueryService;

    public function __construct(RmsProjectQueryService $rmsProjectQueryService)
    {
        $this->rmsProjectQueryService = $rmsProjectQueryService;
    }

    public function forms()
    {
        return response()->json(
            $this->rmsProjectQueryService->getForms()
        );
    }

    public function columns($formId)
    {
        return response()->json(
            $this->rmsProjectQueryService->getColumnsByFormId($formId)
        );
    }

    public function index(Request $request)
    {
        $formId = $request->input('formId', default_form_id);

        return response()->json(
            $this->rmsProjectQueryService->getPaginatedProjects($request)
        );
    }

    /**
     * View Single Record
     */
    public function show($id)
    {
        $record = DB::connection('ers')
            ->table('tblFormITProject')
            ->where('id', $id)
            ->first();

        if (!$record) {
            return response()->json([
                'message' => 'Record not found.'
            ], 404);
        }

        return response()->json($record);
    }

    /**
     * Dashboard Statistics
     */
    public function statistics()
    {
        return response()->json([
            'total' => DB::connection('ers')
            ->table('tblFormITProject')
                ->where('deletedflag', 0)
                ->count(),

            'new' => DB::table('ers_db.tblFormITProject as a')
                ->leftJoin('ers_db.tblstatus as s', 's.id', '=', 'a.CurrentStatusId')
                ->where('a.deletedflag', 0)
                ->where('s.status', 'New')
                ->count(),

            'completed' => DB::table('ers_db.tblFormITProject as a')
                ->leftJoin('ers_db.tblstatus as s', 's.id', '=', 'a.CurrentStatusId')
                ->where('a.deletedflag', 0)
                ->where('s.status', 'Completed')
                ->count(),
        ]);
    }

    /**
     * Send RMS Notification Email
     */
    public function notifyRmsNew()
    {
        $requests = DB::table('ers_db.tblFormITProject as a')
            ->leftJoin('ers_db.tblstatus as s', function ($join) {
                $join->on('s.id', '=', 'a.CurrentStatusId')
                    ->where('s.formid', default_form_id);
            })
            ->leftJoin('ers_db.tbl_globalreference as tgr', 'a.AdditionalTypeId', '=', 'tgr.grid')
            ->select(
                'a.id',
                'a.Title',
                'a.Requestor',
                'a.CreatedDate',
                's.status',
                'tgr.referencename'
            )
            ->where('a.deletedflag', 0)
            ->where('s.status', 'New')
            ->where('tgr.referencename', 'RMS')
            ->get();

        if ($requests->isEmpty()) {
            return response()->json([
                'message' => 'No RMS requests found.'
            ]);
        }

        Mail::to('sjaypits@gmail.com')
            ->send(new RmsNewRequestMail($requests));

        return response()->json([
            'message' => 'Notification email sent.',
            'count' => $requests->count()
        ]);
    }


}