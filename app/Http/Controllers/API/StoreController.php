<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductCategories;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        // POST /orders
    public function orders(Request $r) {
        $r->validate([
            'items'=>'required|array|min:1',
            'items.*.product_id'=>'required|integer|exists:products,id',
            'items.*.qty'=>'required|integer|min:1',
            'billing_address'=>'required|array',
            'shipping_address'=>'nullable|array',
            'platform'=>'required|string', // payment platform meta
        ]);

        DB ::beginTransaction();
        try {
            $items = $r->items;
            $products = Product::whereIn('id', collect($items)->pluck('product_id'))->get()->keyBy('id');

            $subtotal = 0;
            $orderItems = [];

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

                // reduce stock
                $p->decrement('stock', (int)$it['qty']);
            }

            $discount = (float)($r->get('discount', 0));
            $tax      = (float)($r->get('tax', 0));
            $shipping = (float)($r->get('shipping', 0));
            $total    = max(0, $subtotal - $discount + $tax + $shipping);

            $order = Order::create([
                'user_id'=>auth()->id(),
                'status'=>'pending',
                'subtotal'=>$subtotal,
                'discount'=>$discount,
                'tax'=>$tax,
                'shipping'=>$shipping,
                'total'=>$total,
                'currency'=>$r->get('currency','USD'),
                'payment_status'=>'unpaid',
                'billing_address'=>$r->billing_address,
                'shipping_address'=>$r->get('shipping_address', $r->billing_address),
            ]);

            foreach ($orderItems as $row) {
                $order->items()->create($row);
            }

            $order->payments()->create([
                'platform'=>$r->platform,
                'transaction_id'=>$r->get('transaction_id'),
                'amount'=>$total,
                'status'=>'pending',
                'meta'=>$r->get('payment_meta', []),
            ]);

            DB::commit();
            return response()->json(['status'=>true,'message'=>'Order created','data'=>$order->load('items','payments')]);
        } catch (\Throwable $e){
            DB::rollBack();
            return response()->json(['status'=>false,'message'=>$e->getMessage()], 422);
        }
    }

    // GET /orders (user history)
    public function index(Request $r) {
        $q = Order::with('items.product')->where('user_id', auth()->id())->latest();

        if ($r->filled('status')) $q->where('status',$r->status);
        if ($r->filled('min_total')) $q->where('total','>=',(float)$r->min_total);
        if ($r->filled('max_total')) $q->where('total','<=',(float)$r->max_total);

        return $q->paginate($r->get('per_page',15))->appends($r->query());
    }

    // GET /orders/{id}
    public function show($id) {
        $order = Order::with(['items.product','payments'])->where('user_id',auth()->id())->findOrFail($id);
        return $order;
    }


}
