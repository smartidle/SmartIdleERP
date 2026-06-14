<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OperationLog;
use Illuminate\Http\Request;

class OperationLogController extends Controller
{
    public function index(Request $request)
    {
        $query = OperationLog::query();

        if ($request->has('search')) {
            $q = $request->input('search');
            $query->where(function ($sq) use ($q) {
                $sq->where('description', 'like', "%{$q}%")
                   ->orWhere('module', 'like', "%{$q}%")
                   ->orWhere('action', 'like', "%{$q}%");
            });
        }
        if ($request->has('module')) {
            $query->where('module', $request->input('module'));
        }
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->input('employee_id'));
        }

        $list = $query->orderBy('id', 'desc')->paginate($request->input('per_page', 20));
        return $this->success($list);
    }

    public function show(OperationLog $operationLog)
    {
        return $this->success($operationLog);
    }
}
