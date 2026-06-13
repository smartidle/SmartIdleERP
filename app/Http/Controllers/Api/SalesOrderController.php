<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesOrderController extends Controller
{
    public function index(Request $request)
    {
        $query = SalesOrder::with(['customer', 'employee']);
        
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        
        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->input('customer_id'));
        }
        
        if ($request->has('search')) {
            $query->where('order_no', 'like', '%' . $request->input('search') . '%');
        }
        
        $orders = $query->orderBy('id', 'desc')->paginate(20);
        
        return $this->success($orders);
    }

    public function store(Request $request)
    {
        $request->validate([
            'customer_id' => 'required',
            'items' => 'required|array|min:1',
        ]);

        try {
            DB::beginTransaction();
            
            $orderNo = 'SO' . date('Ymd') . str_pad(SalesOrder::count() + 1, 6, '0', STR_PAD_LEFT);
            
            $totalAmount = 0;
            foreach ($request->items as $item) {
                $totalAmount += ($item['quantity'] ?? 0) * ($item['price'] ?? 0);
            }
            
            $order = SalesOrder::create([
                'order_no' => $orderNo,
                'customer_id' => $request->customer_id,
                'warehouse_id' => null, // No warehouse required
                'employee_id' => 1,
                'order_date' => date('Y-m-d'),
                'total_amount' => $totalAmount,
                'discount_amount' => 0,
                'tax_amount' => 0,
                'status' => 1,
                'notes' => $request->notes ?? '',
            ]);

            foreach ($request->items as $item) {
                SalesOrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'sku_id' => $item['sku_id'] ?? null,
                    'product_name' => $item['product_name'] ?? '',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'subtotal' => $item['quantity'] * $item['price'],
                ]);
            }

            DB::commit();
            
            return $this->success($order, 'Order created', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->error('Failed: ' . $e->getMessage());
        }
    }

    public function show(SalesOrder $order)
    {
        $order->load(['customer', 'items.product', 'items.sku']);
        return $this->success($order);
    }

    public function update(Request $request, SalesOrder $order)
    {
        if ($order->status >= 3) {
            return $this->error('Cannot update confirmed orders');
        }

        $order->update($request->only(['customer_id', 'notes']));
        
        if ($request->has('items')) {
            $order->items()->delete();
            foreach ($request->items as $item) {
                SalesOrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'sku_id' => $item['sku_id'] ?? null,
                    'product_name' => $item['product_name'] ?? '',
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['price'],
                    'subtotal' => $item['quantity'] * $item['price'],
                ]);
            }
        }

        return $this->success($order, 'Updated');
    }

    public function destroy(SalesOrder $order)
    {
        if ($order->status > 1) {
            return $this->error('Cannot delete confirmed orders');
        }
        $order->items()->delete();
        $order->delete();
        return $this->success(null, 'Deleted');
    }

    public function approve(SalesOrder $order)
    {
        if ($order->status != 1) {
            return $this->error('Order cannot be approved (current status: ' . $order->status . ')');
        }
        $order->update(['status' => 2]);
        return $this->success($order, 'Approved');
    }

    public function cancel(SalesOrder $order)
    {
        if ($order->status >= 4) {
            return $this->error('Order cannot be cancelled');
        }
        $order->update(['status' => 6]);
        return $this->success(null, 'Cancelled');
    }

    public function delivery(Request $request, SalesOrder $order)
    {
        if ($order->status != 2) {
            return $this->error('Order must be approved first');
        }
        $order->update(['status' => 4]);
        return $this->success($order, 'Delivered');
    }
}
