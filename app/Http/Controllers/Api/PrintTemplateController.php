<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PrintTemplate;
use Illuminate\Http\Request;

class PrintTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = PrintTemplate::query();

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $list = $query->orderBy('is_default', 'desc')->orderBy('id', 'desc')
            ->paginate($request->input('per_page', 20));
        return $this->success($list);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:128',
            'type' => 'required|integer|in:1,2,3,4,5,6,7',
            'content' => 'required|string',
            'is_default' => 'nullable|integer|in:0,1',
        ]);

        // 设为默认时取消其他默认
        if (($validated['is_default'] ?? 0) == 1) {
            PrintTemplate::where('type', $validated['type'])->update(['is_default' => 0]);
        }

        $template = PrintTemplate::create($validated);
        return $this->success($template, '打印模板创建成功', 201);
    }

    public function show(PrintTemplate $printTemplate)
    {
        return $this->success($printTemplate);
    }

    public function update(Request $request, PrintTemplate $printTemplate)
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:128',
            'content' => 'sometimes|string',
            'is_default' => 'nullable|integer|in:0,1',
        ]);

        if (($validated['is_default'] ?? null) == 1) {
            PrintTemplate::where('type', $printTemplate->type)
                ->where('id', '!=', $printTemplate->id)
                ->update(['is_default' => 0]);
        }

        $printTemplate->update($validated);
        return $this->success($printTemplate, '打印模板更新成功');
    }

    public function destroy(PrintTemplate $printTemplate)
    {
        $printTemplate->delete();
        return $this->success(null, '打印模板删除成功');
    }

    /**
     * 根据类型获取默认模板
     */
    public function byType($type)
    {
        $template = PrintTemplate::where('type', $type)
            ->orderBy('is_default', 'desc')
            ->first();
        return $this->success($template);
    }
}
