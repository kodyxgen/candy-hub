<?php
namespace GetCandy\Http\Controllers\Cms\OrderProcessing;

use GetCandy\Http\Controllers\Cms\Controller;

class OrderController extends Controller
{
    public function getIndex()
    {
        return view('order-processing.orders.index');
    }

    public function getEdit($id)
    {
        return view('order-processing.orders.edit', [
            'id' => $id
        ]);
    }

    public function invoice($id)
    {
        $order = app('api')->orders()->getByHashedId($id);
        $pdf = app('api')->orders()->getPdf($order);
        return $pdf->stream('#INV-' . $order->reference . '.pdf');
    }

}