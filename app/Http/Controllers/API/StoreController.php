<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategories;
use App\Services\ThawaniService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class StoreController extends Controller
{
    public function Categories(Request $r) {
        try {
            $data = ProductCategories::where('status',1)->orderBy('name')->paginate($r->get('per_page',10));
            return response()->json([
                'message' => '',
                'status' => true,
                'data' => $data,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function categoryDetails($slug) {
        return ProductCategories::where('slug',$slug)->where('status',1)->firstOrFail();
    }

    public function product(Request $r) {

        try {
            $q = Product::query()
                ->with(['category','images','attributeValues.attribute'])
                ->where('status',1);

            // search (name, slug, description)
            if($term = $r->get('s')){
                $q->where(function($x) use ($term){
                    $x->where('name','LIKE',"%$term%")
                    ->orWhere('slug','LIKE',"%$term%")
                    ->orWhere('description','LIKE',"%$term%");
                });
            }

            // category filter
            if($cid = $r->get('category_id')) {
                $q->where('category_id',$cid);
            }

            // price range
            if($min = $r->get('min_price')) $q->where('price','>=',(float)$min);
            if($max = $r->get('max_price')) $q->where('price','<=',(float)$max);

            // attribute filtering: pass attribute_value_ids[]=1&attribute_value_ids[]=2
            if($av = $r->get('attribute_value_ids')){
                $ids = is_array($av)? $av : explode(',',$av);
                $q->whereHas('attributeValues', fn($h)=>$h->whereIn('attribute_value_id',$ids));
            }

            // sort
            if($sort = $r->get('sort')){
                $map = [
                    'price_asc'=>['price','asc'],
                    'price_desc'=>['price','desc'],
                    'newest'=>['created_at','desc'],
                ];
                if(isset($map[$sort])) $q->orderBy($map[$sort][0],$map[$sort][1]);
            } else {
                $q->latest();
            }

            // get paginated data
            $products = $q->paginate($r->get('per_page',20))->appends($r->query());

            // transform products
            $products->getCollection()->transform(function($product){
                $attributes = $product->attributeValues
                    ->groupBy(fn($item)=>$item->attribute->id)
                    ->map(function($values){
                        return [
                            'name' => $values->first()->attribute->name,
                            'slug' => $values->first()->attribute->slug,
                            'AttrValue' => $values->pluck('value')->toArray(),
                        ];
                    })
                    ->values();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'slug' => $product->slug,
                    'price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'stock' => $product->stock,
                    'images' => $product->images->pluck('url'),
                    'category' => $product->category?->name,
                    'attributes' => $attributes,
                ];
            });

            return response()->json([
                'message' => '',
                'status' => true,
                'products' => $products,
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function producedetails($slug) {

        try{
            $product = Product::with(['category','images','attributeValues.attribute'])
            ->where('slug',$slug)
            ->where('status',1)
            ->firstOrFail();

            // group attributes
            $attributes = $product->attributeValues
                ->groupBy(fn($item)=>$item->attribute->id)
                ->map(function($values){
                    return [
                        'name' => $values->first()->attribute->name,
                        'slug' => $values->first()->attribute->slug,
                        'AttrValue' => $values->pluck('value')->toArray(),
                    ];
                })
                ->values();

            $data = [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'description' => $product->description,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'stock' => $product->stock,
                'images' => $product->images->pluck('url'),
                'category' => $product->category?->name,
                'attributes' => $attributes,
                'created_at' => $product->created_at,
                'updated_at' => $product->updated_at,
            ];

            return response()->json([
                'message' => '',
                'status' => true,
                'product' => $data,
            ]);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * POST /store/checkout
     * Creates order + initiates Thawani payment session.
     * Returns redirect_url for Thawani hosted payment page.
     */
    public function orders(Request $r) {

        $validator = Validator::make($r->all(), [
            'items'               => 'required|array|min:1',
            'items.*.product_id'  => 'required|integer|exists:products,id',
            'items.*.qty'         => 'required|integer|min:1',
            'billing_address'     => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors(), 'status' => false], 422);
        }

        DB::beginTransaction();
        try {
            $items = $r->items;
            $products = Product::whereIn('id', collect($items)->pluck('product_id'))->get()->keyBy('id');

            $subtotal = 0;
            $orderItems = [];
            $thawaniProducts = [];

            foreach ($items as $it){
                $p = $products[$it['product_id']];
                if ($p->stock < $it['qty']) throw new \Exception("Insufficient stock for {$p->name}");
                $price = $p->sale_price ?: $p->price;
                $line  = $price * (int)$it['qty'];
                $subtotal += $line;

                $orderItems[] = [
                    'product_id'=>$p->id,
                    'product_name'=>$p->name,
                    'price'=>$price,
                    'qty'=>$it['qty'],
                    'total'=>$line,
                ];

                // Thawani expects unit_amount in baisa (1 OMR = 1000 baisa)
                $thawaniProducts[] = [
                    'name' => $p->name,
                    'quantity' => (int)$it['qty'],
                    'unit_amount' => (int)($price * 1000), // Convert OMR to baisa
                ];

                // reduce stock
                $p->decrement('stock', (int)$it['qty']);
            }

            $discount = (float)($r->get('discount', 0));
            $tax      = (float)($r->get('tax', 0));
            $shipping = (float)($r->get('shipping', 0));
            $total    = max(0, $subtotal - $discount + $tax + $shipping);

            $order = Order::create([
                'user_id'          =>  auth()->id(),
                'status'           =>  'pending',
                'subtotal'         =>  $subtotal,
                'discount'         =>  $discount,
                'tax'              =>  $tax,
                'shipping'         =>  $shipping,
                'total'            =>  $total,
                'currency'         =>  'OMR',
                'payment_status'   =>  'unpaid',
                'billing_address'  =>  $r->billing_address,
                'shipping_address' =>  $r->get('shipping_address', $r->billing_address),
            ]);

            foreach ($orderItems as $row) {
                $order->items()->create($row);
            }

            // Create Thawani checkout session
            $thawaniService = new ThawaniService();
            $thawaniResult = $thawaniService->createCheckoutSession([
                'client_reference_id' => 'order-' . $order->id,
                'products' => $thawaniProducts,
                'success_url' => url('/api/store/payment/success?order_id=' . $order->id),
                'cancel_url' => url('/api/store/payment/cancel?order_id=' . $order->id),
                'metadata' => [
                    'order_id' => (string)$order->id,
                    'user_id' => (string)auth()->id(),
                ],
            ]);

            if (!$thawaniResult['status']) {
                DB::rollBack();
                return response()->json([
                    'status' => false,
                    'message' => 'Payment session creation failed: ' . ($thawaniResult['message'] ?? 'Unknown error'),
                ], 400);
            }

            // Save Thawani session info in payment record
            $order->payments()->create([
                'platform'       => 'thawani',
                'transaction_id' => $thawaniResult['session_id'],
                'amount'         => $total,
                'status'         => 'unpaid',
                'meta'           => [
                    'thawani_session_id' => $thawaniResult['session_id'],
                    'redirect_url' => $thawaniResult['redirect_url'],
                ],
            ]);

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Order created. Redirect to payment.',
                'data' => [
                    'order_id' => $order->id,
                    'thawani_session_id' => $thawaniResult['session_id'],
                    'redirect_url' => $thawaniResult['redirect_url'],
                ],
            ]);

        } catch (\Throwable $e){
            DB::rollBack();
            return response()->json(['status'=>false,'message'=>$e->getMessage()], 422);
        }
    }

    /**
     * GET /store/payment/success
     * Thawani redirects here after successful payment.
     * Returns HTML that the mobile WebView can detect via URL scheme.
     *
     * Mobile app WebView should intercept URLs starting with "magicapp://".
     * When detected: close the WebView and call /store/payment/verify/{order_id}
     */
    public function paymentSuccess(Request $request)
    {
        $orderId = $request->query('order_id');

        if (!$orderId) {
            return response('<h1>Error: Order ID missing</h1>', 400);
        }

        $order = Order::with('payments')->find($orderId);

        if ($order) {
            // Verify & update payment status immediately
            $payment = $order->payments()->where('platform', 'thawani')->first();
            if ($payment && $payment->meta) {
                $meta = is_array($payment->meta) ? $payment->meta : json_decode($payment->meta, true);
                $sessionId = $meta['thawani_session_id'] ?? null;

                if ($sessionId) {
                    $thawaniService = new ThawaniService();
                    $result = $thawaniService->getSession($sessionId);

                    if ($result['status'] && isset($result['data']['payment_status']) && $result['data']['payment_status'] === 'paid') {
                        $order->update(['payment_status' => 'paid', 'status' => 'confirmed']);
                        $payment->update(['status' => 'paid']);
                    } else {
                        $order->update(['payment_status' => 'pending_verification']);
                    }
                }
            }
        }

        // Return HTML page with deep link for mobile app
        // The WebView intercepts "magicapp://payment/success?order_id=X" and closes itself
        return response()->view('payments.thawani-redirect', [
            'status' => 'success',
            'order_id' => $orderId,
            'message' => 'Payment Successful!',
            'deep_link' => url().'/payment/success?order_id=' . $orderId,
        ]);
    }

    /**
     * GET /store/payment/cancel
     * Thawani redirects here when user cancels payment.
     */
    public function paymentCancel(Request $request)
    {
        $orderId = $request->query('order_id');

        if ($orderId) {
            $order = Order::with('items')->find($orderId);
            if ($order && $order->payment_status !== 'paid') {
                $order->update([
                    'payment_status' => 'cancelled',
                    'status' => 'cancelled',
                ]);

                // Restore stock
                foreach ($order->items as $item) {
                    Product::where('id', $item->product_id)->increment('stock', $item->qty);
                }

                $order->payments()->where('platform', 'thawani')->update(['status' => 'cancelled']);
            }
        }

        return response()->view('payments.thawani-redirect', [
            'status' => 'cancelled',
            'order_id' => $orderId,
            'message' => 'Payment Cancelled',
            'deep_link' => url().'/payment/cancel?order_id=' . $orderId,
        ]);
    }

    /**
     * GET /store/payment/verify/{order_id}
     * Mobile app calls this API AFTER closing the WebView to get order + payment status.
     */
    public function verifyPayment($orderId)
    {
        $order = Order::with('items', 'payments')
            ->where('user_id', auth()->id())
            ->find($orderId);

        if (!$order) {
            return response()->json(['status' => false, 'message' => 'Order not found.'], 404);
        }

        // If still not verified, try verifying with Thawani again
        if (!in_array($order->payment_status, ['paid', 'cancelled'])) {
            $payment = $order->payments()->where('platform', 'thawani')->first();
            if ($payment && $payment->meta) {
                $meta = is_array($payment->meta) ? $payment->meta : json_decode($payment->meta, true);
                $sessionId = $meta['thawani_session_id'] ?? null;

                if ($sessionId) {
                    $thawaniService = new ThawaniService();
                    $result = $thawaniService->getSession($sessionId);

                    if ($result['status'] && isset($result['data']['payment_status'])) {
                        $thawaniStatus = $result['data']['payment_status'];

                        if ($thawaniStatus === 'paid') {
                            $order->update(['payment_status' => 'paid', 'status' => 'confirmed']);
                            $payment->update(['status' => 'paid']);
                        } elseif ($thawaniStatus === 'cancelled') {
                            $order->update(['payment_status' => 'cancelled', 'status' => 'cancelled']);
                            $payment->update(['status' => 'cancelled']);
                            // Restore stock
                            foreach ($order->items as $item) {
                                Product::where('id', $item->product_id)->increment('stock', $item->qty);
                            }
                        }

                        $order->refresh();
                    }
                }
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Order payment status: ' . $order->payment_status,
            'data' => $order->load('items', 'payments'),
        ]);
    }

    // GET /orders (user history)
    public function orderHistory(Request $r) {
        $q = Order::with('items','payments')->where('user_id', auth()->id())->latest();

        if ($r->filled('status')) $q->where('status',$r->status);
        if ($r->filled('min_total')) $q->where('total','>=',(float)$r->min_total);
        if ($r->filled('max_total')) $q->where('total','<=',(float)$r->max_total);

        $data =  $q->paginate($r->get('per_page',15))->appends($r->query());
        return response()->json(['status'=>true,'message'=>'','data'=> $data]);
    }

    // GET /orders/{id}
    public function orderDetails($id) {
        $order = Order::with(['items.product.images','payments'])->where('user_id',auth()->id())->findOrFail($id);
        return response()->json(['status'=>true,'message'=>'','data'=> $order]);
    }


}
