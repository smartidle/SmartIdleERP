<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApprovalFlow;
use App\Models\ApprovalRecord;
use App\Models\ApprovalDelegate;
use Illuminate\Http\Request;

class ApprovalController extends Controller
{
    /**
     * 待我审批的列表
     */
    public function pending(Request $request)
    {
        $user = $request->user();
        
        // 获取当前用户的待审批记录
        $records = ApprovalRecord::with(['flow', 'applicant'])
            ->where('approver_id', $user->id)
            ->where('status', 1) // 待审批
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->success($records);
    }

    /**
     * 我发起的审批
     */
    public function myApplications(Request $request)
    {
        $user = $request->user();
        
        $records = ApprovalRecord::with(['flow', 'currentApprover'])
            ->where('applicant_id', $user->id)
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->success($records);
    }

    /**
     * 我已审批的记录
     */
    public function myApprovals(Request $request)
    {
        $user = $request->user();
        
        $records = ApprovalRecord::with(['flow', 'applicant'])
            ->whereHas('approvalSteps', function ($q) use ($user) {
                $q->where('approver_id', $user->id)
                    ->where('status', '!=', 1);
            })
            ->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));

        return $this->success($records);
    }

    /**
     * 审批操作
     */
    public function approve(Request $request, ApprovalRecord $record)
    {
        $request->validate([
            'action' => 'required|in:approve,reject',
            'comment' => 'nullable|string|max:500',
            'next_approver_id' => 'nullable|exists:employees,id',
        ]);

        $user = $request->user();

        // 检查是否有审批权限
        $currentStep = $record->currentStep();
        if (!$currentStep || $currentStep->approver_id !== $user->id) {
            return $this->error('You do not have permission to approve this', 403);
        }

        if ($request->input('action') === 'approve') {
            // 查找下一步审批人
            $nextStep = $record->nextStep();
            
            if ($nextStep) {
                // 还有下一步
                $currentStep->update([
                    'status' => 2, // 已同意
                    'comment' => $request->input('comment'),
                    'approved_at' => now(),
                ]);
                
                $nextStep->update(['approver_id' => $request->input('next_approver_id')]);
                
                $record->update(['current_step' => $nextStep->step_no]);
            } else {
                // 审批完成
                $currentStep->update([
                    'status' => 2,
                    'comment' => $request->input('comment'),
                    'approved_at' => now(),
                ]);
                
                $record->update([
                    'status' => 2, // 已通过
                    'completed_at' => now(),
                    'current_step' => $record->total_steps,
                ]);

                // 执行审批通过后的业务逻辑
                $this->executeBusinessLogic($record);
            }
            
            $message = 'Approved successfully';
        } else {
            // 拒绝
            $currentStep->update([
                'status' => 3, // 已拒绝
                'comment' => $request->input('comment'),
                'approved_at' => now(),
            ]);
            
            $record->update([
                'status' => 3, // 已拒绝
                'completed_at' => now(),
            ]);
            
            $message = 'Rejected successfully';
        }

        return $this->success(null, $message);
    }

    /**
     * 发起审批流程
     */
    public function submit(Request $request)
    {
        $request->validate([
            'flow_id' => 'required|exists:approval_flows,id',
            'order_type' => 'required|string',
            'order_id' => 'required|integer',
        ]);

        $flow = ApprovalFlow::find($request->input('flow_id'));
        $user = $request->user();

        // 创建审批记录
        $record = ApprovalRecord::create([
            'flow_id' => $flow->id,
            'order_type' => $request->input('order_type'),
            'order_id' => $request->input('order_id'),
            'applicant_id' => $user->id,
            'total_steps' => $flow->total_steps,
            'current_step' => 1,
            'status' => 1,
        ]);

        // 创建审批步骤
        $stepApprovers = json_decode($flow->step_approvers, true) ?? [];
        foreach ($stepApprovers as $index => $approverId) {
            ApprovalStep::create([
                'record_id' => $record->id,
                'step_no' => $index + 1,
                'approver_id' => $approverId,
                'status' => 1,
            ]);
        }

        return $this->success($record, 'Application submitted', 201);
    }

    /**
     * 审批委托设置
     */
    public function delegates(Request $request)
    {
        $user = $request->user();
        
        $delegates = ApprovalDelegate::where('delegator_id', $user->id)
            ->where('status', 1)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->get();

        return $this->success($delegates);
    }

    /**
     * 创建委托
     */
    public function createDelegate(Request $request)
    {
        $request->validate([
            'delegatee_id' => 'required|exists:employees,id',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after:start_date',
            'flow_ids' => 'required|array',
        ]);

        $user = $request->user();

        $delegate = ApprovalDelegate::create([
            'delegator_id' => $user->id,
            'delegatee_id' => $request->input('delegatee_id'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'flow_ids' => json_encode($request->input('flow_ids')),
            'status' => 1,
        ]);

        return $this->success($delegate, 'Delegate created', 201);
    }

    /**
     * 执行审批通过后的业务逻辑
     */
    private function executeBusinessLogic($record)
    {
        // 根据order_type执行不同的业务逻辑
        switch ($record->order_type) {
            case 'sales_order':
                $order = SalesOrder::find($record->order_id);
                if ($order) {
                    $order->status = 2; // 已审批
                    $order->save();
                }
                break;
            case 'purchase_order':
                $order = PurchaseOrder::find($record->order_id);
                if ($order) {
                    $order->status = 2;
                    $order->save();
                }
                break;
        }
    }
}

// 需要添加 ApprovalStep 模型关联到 ApprovalRecord
