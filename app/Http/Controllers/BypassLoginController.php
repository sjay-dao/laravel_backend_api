<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BypassLoginController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required',
            'password' => 'required'
        ]);

        if ($request->password !== env('BYPASS_PASSWORD')) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid bypass password'
            ], 401);
        }

        $user = DB::connection('ems_plantilla')->selectOne("
            SELECT
                u.id,
                u.code,
                u.firstname,
                u.lastname,
                su.username,
                org.org_type,
                org.code AS org_code,
                org.description AS org_description,
                br.branch_code,
                br.branch_name,
                pos.title AS position_title,
                pos.code AS position_code
            FROM ems_plantilla.employee u
            LEFT JOIN ems_system_access.system_user su
                ON u.code = su.username
            LEFT JOIN ems_plantilla.org_group org
                ON org.id = u.org_group_id
            -- LEFT JOIN rms_database.branches br
            --    ON br.branch_code = org.code
            LEFT JOIN ems_plantilla.position pos
                ON pos.id = u.position_id
            WHERE su.username = ?
            LIMIT 1
        ", [$request->username]);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'user' => [
                'code' => $user->code,
                'orgType' => $user->org_type,
                'positionTitle' => $user->position_title,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'branchCode' => $user->branch_code,
                'branchName' => $user->branch_name,
            ]
        ]);
    }
}