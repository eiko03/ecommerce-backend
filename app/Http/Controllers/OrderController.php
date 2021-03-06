<?php

namespace App\Http\Controllers;

use App\Http\Requests\Order\ChangeStatusofOrderRequest;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Models\Order;
use App\Models\OrderHistory;
use App\Traits\OrderTrait;

class OrderController extends Controller
{
    use OrderTrait;
    private $current_cart=0;
    private $approved=1;
    private $rejected=2;
    private $processing=3;
    private $shipped=4;
    private $delivered=5;

//    private function group_by($array, $key){
//        $return = array();
//        foreach ($array as $val)
//            return [$val[$key]][]=$val;
//        return $return;
//    }
//
//    public function index(){
//        $oh=OrderHistory::where('id','>',0)->pluck('order_history_id');
//        $orders=[];
//        $max_el=[];
//        return response()->json($oh);
//        foreach($oh as $ohs)
//            $max_el[]=OrderHistory::where('order_history_id',$ohs)->max('edit_level')->pluck('edit_level');
//
//        foreach($max_el as $key=>$el)
//            $orders[]=OrderHistory::where([['edit_level',$el],['order_history_id',$oh[$key]]])->with('orders')->with('products')->get()->toArray();
//
//
//
//        $order_histories=$this->group_by(orders,'order_history_id');
//        return response()->json($order_histories);
//    }
    public function index(){
        $order_histories=OrderHistory::with('orders')->with('products')->get()->groupBy('order_history_id');
        return response()->json($order_histories);
    }

    public function show($OrderId){
        $order_histories=OrderHistory::where([['edit_level',0],['order_history_id',$OrderId]])->with('orders')->with('products')->get();
        return response()->json($order_histories);
    }

    public function show_history($OrderId){
        $order_histories=OrderHistory::where([['edit_level','>',0],['order_history_id',$OrderId]])->with('orders')->with('products')->get()->groupBy('edit_level');
        return response()->json($order_histories);
    }


    public function store(CreateOrderRequest $request){
        $time=intval(intval(now()->timestamp)/rand(9,199));
        $this->UpdateOrder($request->get("orders"),$time);
        return response()->json(["message"=>"Order Successful", "order_id"=>$time],201);
    }

    public function update($OrderId,CreateOrderRequest $request){
        $order_history=OrderHistory::where([['order_history_id',$OrderId],['edit_level',$this->current_cart]]);
        if($order_history->get()->isEmpty()) return response(["message"=>"Order Not Found"],404);
        foreach ( $order_history as $single_order)  if ($single_order->order_status!=0)  return response(["message"=>"Order Is Processing"],500);
        $order_history->increment('edit_level');
        $this->UpdateOrder($request->get("orders"),$OrderId);
        return response()->json(["message"=>"Order Update Successful", "order_id"=>$OrderId],201);
    }

    public function change_status($OrderId,ChangeStatusofOrderRequest $request){
        $order_histories=OrderHistory::where([['order_history_id',$OrderId],['edit_level',$this->current_cart]])->pluck('id')->toArray();
        if (empty($order_histories))
            return response()->json(["message"=>"Order Not Found"],500);
        Order::whereIn('order_id',$order_histories)->update(['status'=>$request->status_id]);
        return response()->json(["message"=>"Rejected Successfully"],200);
    }


}
