<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Delivery;
use App\Traits\HandlesImages;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    use HandlesImages;

    private function generateFolio()
    {
        $now = now();
        $month = str_pad($now->month, 2, '0', STR_PAD_LEFT);
        $prefix = "DXZ-{$month}{$now->year}-";
        
        $lastOrder = Order::where('folio', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();
        
        $next = 1;
        if ($lastOrder) {
            $lastNumber = intval(substr($lastOrder->folio, -5));
            $next = $lastNumber + 1;
        }
        
        return $prefix . str_pad($next, 5, '0', STR_PAD_LEFT);
    }

    public function index(Request $request)
    {
        try {
            // VENTAS solo puede ver, no editar (pero puede ver todo)
            $query = Order::with([
                'customer', 
                'device.deviceType', 
                'device.brand', 
                'status',
                'payments',
                'deliveries'
            ]);
            
            // Filtros
            if ($request->has('folio')) {
                $query->where('folio', 'like', "%{$request->folio}%");
            }
            
            if ($request->has('customer')) {
                $search = "%{$request->customer}%";
                $query->whereHas('customer', function($q) use ($search) {
                    $q->where('first_name', 'like', $search)
                      ->orWhere('last_name', 'like', $search)
                      ->orWhere('phone', 'like', $search);
                });
            }
            
            if ($request->has('status')) {
                $query->whereHas('status', function($q) use ($request) {
                    $q->where('code', $request->status);
                });
            }

            // Filtros por diagnóstico/seguimiento/solución
            if ($request->has('has_diagnosis')) {
                $query->whereNotNull('diagnosis');
            }
            
            if ($request->has('has_solution')) {
                $query->whereNotNull('solution');
            }
            
            $perPage = $request->get('limit', 10);
            $page = $request->get('page', 1);
            
            $orders = $query->orderBy('created_at', 'desc')
                ->paginate($perPage, ['*'], 'page', $page);
            
            return response()->json([
                'data' => $orders->items(),
                'pagination' => [
                    'page' => $orders->currentPage(),
                    'limit' => $orders->perPage(),
                    'total' => $orders->total(),
                    'totalPages' => $orders->lastPage()
                ]
            ], Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            // Verificar permisos: ADMIN, TECNICO, VENTAS pueden crear
            if (!auth()->user()->isAdmin() && !auth()->user()->isTecnico() && !auth()->user()->isVentas()) {
                return response()->json(['error' => 'No tienes permiso para crear órdenes'], 403);
            }

            DB::beginTransaction();
            
            $validated = $request->validate([
                'customer_id' => 'required|exists:customers,id',
                'device_id' => 'required|exists:devices,id',
                'issue_reported' => 'required|string',
                'technical_notes' => 'nullable|string',
                'estimated_cost' => 'nullable|numeric',
                'estimated_days' => 'nullable|integer',
                'promised_date' => 'nullable|date'
            ]);
            
            // Generar folio
            $validated['folio'] = $this->generateFolio();
            
            // Obtener status_id para 'ABIERTO'
            $abiertoStatus = \App\Models\Status::where('code', 'ABIERTO')->first();
            $validated['status_id'] = $abiertoStatus->id;
            
            $validated['created_by'] = auth()->user()->name ?? 'Sistema';
            
            // Crear orden
            $order = Order::create($validated);
            
            // Crear historial
            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status_id' => $abiertoStatus->id,
                'notes' => 'Orden creada',
                'changed_by' => $validated['created_by']
            ]);
            
            DB::commit();
            
            return response()->json(
                $order->load(['customer', 'device', 'status']), 
                Response::HTTP_CREATED
            );
            
        } catch (\Exception $error) {
            DB::rollBack();
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $order = Order::with([
                'customer',
                'device.deviceType',
                'device.brand',
                'status',
                'statusHistory.status',
                'notes' => function($q) {
                    $q->orderBy('created_at', 'desc');
                },
                'payments',
                'deliveries',
                'repairs'
            ])->find($id);
            
            if (!$order) {
                return response()->json(['error' => 'Orden no encontrada'], 404);
            }
            
            return response()->json($order, Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function stats()
{
    try {
        $ordersByStatus = Order::select('status_id', DB::raw('count(*) as total'))
            ->with('status')
            ->groupBy('status_id')
            ->get()
            ->mapWithKeys(function($item) {
                return [$item->status->code => $item->total];
            });

        return response()->json($ordersByStatus, Response::HTTP_OK);

    } catch (\Exception $error) {
        return response()->json(['error' => $error->getMessage()], 500);
    }
}
    public function update(Request $request, $id)
    {
        try {
            // Solo ADMIN y TECNICO pueden editar
            if (!auth()->user()->isAdmin() && !auth()->user()->isTecnico()) {
                return response()->json(['error' => 'No tienes permiso para editar órdenes'], 403);
            }

            DB::beginTransaction();
            
            $order = Order::find($id);
            if (!$order) {
                return response()->json(['error' => 'Orden no encontrada'], 404);
            }
            
            $validated = $request->validate([
                'issue_reported' => 'sometimes|string',
                'technical_notes' => 'nullable|string',
                'estimated_cost' => 'nullable|numeric',
                'estimated_days' => 'nullable|integer',
                'promised_date' => 'nullable|date',
                'assigned_to' => 'nullable|string'
            ]);
            
            $order->update($validated);
            
            DB::commit();
            
            return response()->json($order->load(['customer', 'device', 'status']), Response::HTTP_OK);
            
        } catch (\Exception $error) {
            DB::rollBack();
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    // NUEVO: Guardar diagnóstico (solo TECNICO y ADMIN)
    public function saveDiagnosis(Request $request, $id)
    {
        try {
            if (!auth()->user()->isAdmin() && !auth()->user()->isTecnico()) {
                return response()->json(['error' => 'Solo técnicos pueden agregar diagnóstico'], 403);
            }

            $order = Order::find($id);
            if (!$order) {
                return response()->json(['error' => 'Orden no encontrada'], 404);
            }

            $validated = $request->validate([
                'diagnosis' => 'required|string',
                'diagnosis_images' => 'nullable|array',
                'diagnosis_images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
            ]);

            DB::beginTransaction();

            $order->diagnosis = $validated['diagnosis'];

            // Subir imágenes de diagnóstico
            if ($request->hasFile('diagnosis_images')) {
                $images = [];
                foreach ($request->file('diagnosis_images') as $image) {
                    $imageData = $this->uploadImage($image, 'orders/diagnosis');
                    $images[] = $imageData;
                }
                $order->diagnosis_images = $images;
            }

            $order->save();

            // Agregar nota automática
            \App\Models\OrderNote::create([
                'order_id' => $order->id,
                'note' => '🔧 Diagnóstico agregado: ' . substr($validated['diagnosis'], 0, 100) . '...',
                'is_internal' => true,
                'created_by' => auth()->user()->name
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Diagnóstico guardado',
                'order' => $order
            ], Response::HTTP_OK);

        } catch (\Exception $error) {
            DB::rollBack();
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    // NUEVO: Guardar seguimiento (solo TECNICO y ADMIN)
    public function saveFollowUp(Request $request, $id)
    {
        try {
            if (!auth()->user()->isAdmin() && !auth()->user()->isTecnico()) {
                return response()->json(['error' => 'Solo técnicos pueden agregar seguimiento'], 403);
            }

            $order = Order::find($id);
            if (!$order) {
                return response()->json(['error' => 'Orden no encontrada'], 404);
            }

            $validated = $request->validate([
                'follow_up' => 'required|string',
                'follow_up_images' => 'nullable|array',
                'follow_up_images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
            ]);

            DB::beginTransaction();

            $order->follow_up = $validated['follow_up'];

            if ($request->hasFile('follow_up_images')) {
                $images = [];
                foreach ($request->file('follow_up_images') as $image) {
                    $imageData = $this->uploadImage($image, 'orders/followup');
                    $images[] = $imageData;
                }
                $order->follow_up_images = $images;
            }

            $order->save();

            \App\Models\OrderNote::create([
                'order_id' => $order->id,
                'note' => '📝 Seguimiento: ' . substr($validated['follow_up'], 0, 100) . '...',
                'is_internal' => true,
                'created_by' => auth()->user()->name
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Seguimiento guardado',
                'order' => $order
            ], Response::HTTP_OK);

        } catch (\Exception $error) {
            DB::rollBack();
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    // NUEVO: Guardar solución (solo TECNICO y ADMIN)
    public function saveSolution(Request $request, $id)
    {
        try {
            if (!auth()->user()->isAdmin() && !auth()->user()->isTecnico()) {
                return response()->json(['error' => 'Solo técnicos pueden agregar solución'], 403);
            }

            $order = Order::find($id);
            if (!$order) {
                return response()->json(['error' => 'Orden no encontrada'], 404);
            }

            $validated = $request->validate([
                'solution' => 'required|string',
                'solution_images' => 'nullable|array',
                'solution_images.*' => 'image|mimes:jpeg,png,jpg|max:2048'
            ]);

            DB::beginTransaction();

            $order->solution = $validated['solution'];

            if ($request->hasFile('solution_images')) {
                $images = [];
                foreach ($request->file('solution_images') as $image) {
                    $imageData = $this->uploadImage($image, 'orders/solution');
                    $images[] = $imageData;
                }
                $order->solution_images = $images;
            }

            $order->save();

            \App\Models\OrderNote::create([
                'order_id' => $order->id,
                'note' => '✅ Solución: ' . substr($validated['solution'], 0, 100) . '...',
                'is_internal' => false, // La solución puede ser visible para el cliente
                'created_by' => auth()->user()->name
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Solución guardada',
                'order' => $order
            ], Response::HTTP_OK);

        } catch (\Exception $error) {
            DB::rollBack();
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }

    public function changeStatus(Request $request, $id)
{
    try {
        // Solo ADMIN y TECNICO pueden cambiar estado
        if (!auth()->user()->isAdmin() && !auth()->user()->isTecnico()) {
            return response()->json(['error' => 'No tienes permiso para cambiar el estado'], 403);
        }

        DB::beginTransaction();
        
        $order = Order::find($id);
        if (!$order) {
            return response()->json(['error' => 'Orden no encontrada'], 404);
        }
        
        $validated = $request->validate([
            'status_code' => 'required|exists:statuses,code',
            'notes' => 'nullable|string'
        ]);
        
        $newStatus = \App\Models\Status::where('code', $validated['status_code'])->first();
        $changedBy = auth()->user()->name ?? 'Sistema';
        
        // Validaciones específicas por estado
        if ($validated['status_code'] === 'TERMINADO') {
            // Validar que tenga solución
            if (!$order->solution) {
                return response()->json(['error' => 'No se puede cerrar la orden sin una solución'], 400);
            }
            
            // Validar datos de pago si viene TERMINADO
            $request->validate([
                'service_cost' => 'required|numeric',
                'payment_method' => 'required|string',
                'received_by' => 'required|string',
                'delivery_notes' => 'nullable|string'
            ]);
        }
        
        // Actualizar estado
        $order->status_id = $newStatus->id;
        $order->save();
        
        // Crear historial
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status_id' => $newStatus->id,
            'notes' => $validated['notes'] ?? "Estado cambiado a {$newStatus->name}",
            'changed_by' => $changedBy
        ]);
        
        // Si es TERMINADO, registrar pago y entrega
        if ($validated['status_code'] === 'TERMINADO') {
            Payment::create([
                'order_id' => $order->id,
                'amount' => $request->service_cost,
                'payment_method' => $request->payment_method,
                'received_by' => $changedBy,
                'notes' => $request->notes ?? 'Pago por servicio'
            ]);
            
            Delivery::create([
                'order_id' => $order->id,
                'received_by' => $request->received_by,
                'notes' => $request->delivery_notes ?? 'Equipo entregado',
                'delivered_at' => now()
            ]);
        }
        
        DB::commit();
        
        return response()->json([
            'success' => true,
            'message' => 'Estado actualizado',
            'order' => $order->load(['status', 'payments', 'deliveries'])
        ], Response::HTTP_OK);
        
    } catch (\Exception $error) {
        DB::rollBack();
        return response()->json(['error' => $error->getMessage()], 500);
    }
}
    public function destroy($id)
    {
        try {
            // Solo ADMIN puede eliminar
            if (!auth()->user()->isAdmin()) {
                return response()->json(['error' => 'Solo administradores pueden eliminar órdenes'], 403);
            }

            $order = Order::find($id);
            if (!$order) {
                return response()->json(['error' => 'Orden no encontrada'], 404);
            }
            
            $order->delete();
            
            return response()->json(['message' => 'Orden eliminada'], Response::HTTP_OK);
            
        } catch (\Exception $error) {
            return response()->json(['error' => $error->getMessage()], 500);
        }
    }
    // app/Http/Controllers/API/OrderController.php
public function dashboard(Request $request)
{
    try {
        // Solo ADMIN y TECNICO pueden ver dashboard
        if (!auth()->user()->isAdmin() && !auth()->user()->isTecnico()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $date = $request->get('date', now()->format('Y-m-d'));
        
        // Órdenes del día
        $dailyOrders = Order::with(['customer', 'status'])
            ->whereDate('created_at', $date)
            ->count();

        // Ingresos del día (solo órdenes TERMINADO con pagos)
        $dailyIncome = Payment::whereDate('created_at', $date)
            ->sum('amount');

        // Órdenes por estado
        $ordersByStatus = Order::select('status_id', DB::raw('count(*) as total'))
            ->with('status')
            ->groupBy('status_id')
            ->get()
            ->map(function($item) {
                return [
                    'status' => $item->status->name,
                    'status_code' => $item->status->code,
                    'color' => $item->status->color,
                    'total' => $item->total
                ];
            });

        // Top técnicos (quién ha cerrado más órdenes)
        $topTechs = Order::where('status_id', function($q) {
                $q->select('id')->from('statuses')->where('code', 'TERMINADO');
            })
            ->select('created_by', DB::raw('count(*) as total'))
            ->groupBy('created_by')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get();

        // Últimas 10 órdenes
        $recentOrders = Order::with(['customer', 'status'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function($order) {
                return [
                    'folio' => $order->folio,
                    'customer' => $order->customer->full_name ?? 'N/A',
                    'status' => $order->status->name,
                    'status_color' => $order->status->color,
                    'created_at' => $order->created_at->format('Y-m-d H:i'),
                    'amount' => $order->payments()->sum('amount')
                ];
            });

        return response()->json([
            'date' => $date,
            'daily_orders' => $dailyOrders,
            'daily_income' => $dailyIncome,
            'orders_by_status' => $ordersByStatus,
            'top_technicians' => $topTechs,
            'recent_orders' => $recentOrders
        ]);

    } catch (\Exception $error) {
        return response()->json(['error' => $error->getMessage()], 500);
    }
}
// app/Http/Controllers/API/OrderController.php
public function incomeReport(Request $request)
{
    try {
        if (!auth()->user()->isAdmin() && !auth()->user()->isTecnico()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $startDate = $request->get('start_date', now()->startOfMonth());
        $endDate = $request->get('end_date', now()->endOfMonth());

        // Ingresos agrupados por día
        $dailyIncome = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as total'),
                DB::raw('COUNT(*) as payments_count')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        // Ingresos por método de pago
        $paymentMethods = Payment::whereBetween('created_at', [$startDate, $endDate])
            ->select('payment_method', DB::raw('SUM(amount) as total'))
            ->groupBy('payment_method')
            ->get();

        // Totales del período
        $totals = [
            'total_income' => Payment::whereBetween('created_at', [$startDate, $endDate])->sum('amount'),
            'total_orders' => Order::whereBetween('created_at', [$startDate, $endDate])->count(),
            'completed_orders' => Order::whereBetween('created_at', [$startDate, $endDate])
                ->whereHas('status', function($q) {
                    $q->where('code', 'TERMINADO');
                })->count(),
            'average_ticket' => Payment::whereBetween('created_at', [$startDate, $endDate])->avg('amount')
        ];

        return response()->json([
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ],
            'totals' => $totals,
            'daily_breakdown' => $dailyIncome,
            'payment_methods' => $paymentMethods
        ]);

    } catch (\Exception $error) {
        return response()->json(['error' => $error->getMessage()], 500);
    }
}
// En OrderController@show, ya tenemos payments incluidos
// Pero podemos agregar un endpoint específico:

public function ticketSummary($id)
{
    try {
        $order = Order::with([
            'customer',
            'device.deviceType',
            'device.brand',
            'status',
            'payments',
            'repairs'
        ])->find($id);

        if (!$order) {
            return response()->json(['error' => 'Orden no encontrada'], 404);
        }

        $totalPaid = $order->payments->sum('amount');
        $totalRepairCost = $order->repairs->sum('cost');
        $profit = $totalPaid - $totalRepairCost;

        return response()->json([
            'order' => [
                'folio' => $order->folio,
                'created_at' => $order->created_at->format('Y-m-d H:i'),
                'status' => $order->status->name,
                'customer' => $order->customer->full_name,
                'device' => "{$order->device->brand->name} {$order->device->model}"
            ],
            'financial' => [
                'total_paid' => $totalPaid,
                'repair_costs' => $totalRepairCost,
                'profit' => $profit,
                'profit_margin' => $totalPaid > 0 ? round(($profit / $totalPaid) * 100, 2) : 0
            ],
            'payments' => $order->payments,
            'repairs' => $order->repairs,
            'diagnosis' => $order->diagnosis,
            'solution' => $order->solution
        ]);

    } catch (\Exception $error) {
        return response()->json(['error' => $error->getMessage()], 500);
    }
}
}