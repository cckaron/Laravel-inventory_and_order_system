<?php

namespace App\Http\Controllers;

use App\Order;
use App\Product;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ManageController extends Controller
{
    public function getIndex(){

        $columns = Schema::getColumnListing('orders');
        $orders = Order::all();

        return view('manage.admin', ['columns' => $columns], ['orders' => $orders]);
    }

    public function destroyOrder($id){
        DB::table('orders')->where('id','=', $id)->delete();

        return redirect()->back();
    }

    public function getProduct(){
        $products = DB::table('products')->orderBy('sequence', 'desc')->get();
        return view('manage.products', ['products' => $products]);
    }


    public function addProduct(){

        return view('manage.addProduct');
    }

    public function postAddProduct(Request $request){
        $this->validate($request, [
            'title' => 'required',
            'price' => 'required',
            'unit' => 'required',
            'thickSlice' => 'required',
            'thinSlice' => 'required',
        ]);

        $id_thickSlice = '';
        $id_thinSlice = '';
//        $id_product = (string) Str::uuid();
        $product_title = $request->input('title');
        $id_notSlice = $product_title;


        $canSlice = False;

        if ($request->input('thickSlice')){
            $id_thickSlice = $product_title.'_切厚片';
        }
        if ($request->input('thinSlice')){
            $id_thinSlice = $product_title.'_切薄片';
        }

        if ($request->input('thickSlice') || $request->input('thinSlice')){
            $canSlice = True;
        }


        $product = new Product([
            'title' => $product_title,
            'price' => $request->input('price'),
            'unit' => $request->input('unit'),
            'description' => $request->input('price'),
            'canSlice' => $canSlice,
            'thickSlice' => $request->input('thickSlice'),
            'thinSlice' => $request->input('thinSlice'),
            'id_notSlice' => $id_notSlice,
            'id_thickSlice' => $id_thickSlice,
            'id_thinSlice' => $id_thinSlice,

        ]);
        $product->save();

        $type = 'Integer';


        Schema::table('orders', function (Blueprint $table) use ($type, $id_notSlice){
            $table->$type($id_notSlice)->nullable()->default(0);
        });

        if ($request->input('thickSlice')){
            Schema::table('orders', function (Blueprint $table) use ($type, $id_thickSlice){
                $table->$type($id_thickSlice)->nullable()->default(0);
            });
        }

        if ($request->input('thinSlice')){
            Schema::table('orders', function (Blueprint $table) use ($type, $id_thinSlice){
                $table->$type($id_thinSlice)->nullable()->default(0);
            });
        }


        return redirect()->back();
    }

    public function editProduct($id){

        $products = DB::table('products')->where('id','=', $id)->get();

        return view('manage.editProduct', ['products' => $products]);
    }

    public function postEditProduct(Request $request){
        $this->validate($request, [
            'title' => 'required',
            'price' => 'required',
            'unit' => 'required',
            'thickSlice' => 'required',
            'thinSlice' => 'required',
        ]);

        $id = $request->input('id');

        $product = Product::findOrFail($id);


        $product->price = $request->input('price');
        $product->unit = $request->input('unit');
        $product->description = $request->input('description');
        $product->thickSlice = $request->input('thickSlice');
        $product->thinSlice = $request->input('thinSlice');


        $type = 'Boolean';

        $req_thickSlice = $request->input('thickSlice');
        $req_thinSlice = $request->input('thinSlice');


        // Delete column in orders if slice changed
        if ($req_thickSlice == False){
                $id_thickSlice = $product->id_thickSlice;

                if (Schema::hasColumn('orders', $id_thickSlice)){
                    Schema::table('orders', function(Blueprint $table) use ($id_thickSlice) {
                        $table->dropColumn($id_thickSlice);
                    });
                }

        } else {
            $id_thickSlice = $product->id_thickSlice;
            // add it if not has this column yet
            if (!Schema::hasColumn('orders', $id_thickSlice)){
                Schema::table('orders', function (Blueprint $table) use ($type, $id_thickSlice){
                    $table->$type($id_thickSlice)->nullable()->default(0);
            });
            }
        }

        if ($req_thinSlice == False){
            $id_thinSlice = $product->id_thinSlice;

            if (Schema::hasColumn('orders', $id_thinSlice)){
                Schema::table('orders', function(Blueprint $table) use ($id_thinSlice) {
                    $table->dropColumn($id_thinSlice);
                });
            }

        } else {
            $id_thinSlice = $product->id_thinSlice;
            // add it if not has this column yet
            if (!Schema::hasColumn('orders', $id_thinSlice)){
                Schema::table('orders', function (Blueprint $table) use ($type, $id_thinSlice){
                    $table->$type($id_thinSlice)->nullable()->default(0);
                });
            }
        }



        $product->save();

        if ($product->thickSlice == False && $product->thinSlice == False){
            $product->canSlice = False;
        } else {
            $product->canSlice = True;
        }
        $product->save();




        return redirect()->back();
    }

    public function destroyProduct($id){
        $product = DB::table('products')->where('id','=', $id);

        $id_notSlice = $product->get(['id_notSlice'])[0]->id_notSlice;

        // 無論如何產品一定有notSlice，所以要刪掉orders內的id_notSlice
        Schema::table('orders', function(Blueprint $table) use ($id_notSlice) {
            $table->dropColumn($id_notSlice);
        });

        // 如果產品thickSlice為true，就刪掉orders內的id_thickSlice
        if ($product->get(['thickSlice'])[0]->thickSlice){
            $id_thickSlice = $product->get(['id_thickSlice'])[0]->id_thickSlice;
            Schema::table('orders', function(Blueprint $table) use ($id_thickSlice) {
                $table->dropColumn($id_thickSlice);
            });
        }

        // 如果產品thinSlice為true，就刪掉orders內的id_thinSlice
        if ($product->get(['thinSlice'])[0]->thinSlice){
            $id_thinSlice = $product->get(['id_thinSlice'])[0]->id_thinSlice;
            Schema::table('orders', function(Blueprint $table) use ($id_thinSlice) {
                $table->dropColumn($id_thinSlice);
            });
        }

        // 最後再刪掉product
        DB::table('products')->where('id','=', $id)->delete();

        // 更新order的總數量
        $orders = Order::all();
        $columns = Schema::getColumnListing('orders');
        $all = '總數量';

        foreach ($orders as $order){
            $total = 0;
            for ($i=5; $i<count($columns); $i++){
                $column = $columns[$i];
                $orderValue = $order->$column;
                $total += (int)$orderValue;
            }
            $order->$all = $total;
            $order->save();
        }

        return redirect()->back();
    }
}
