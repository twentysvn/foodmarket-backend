<?php

namespace App\Http\Controllers\API;

use Midtrans\Config;
use Midtrans\Notification;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Transaction;

class MidtransController extends Controller
{
    public function callback(Request $request)
    {
        // set konfigurasi midtrans
        Config::$serverKey = config('services.midtrans.serverKey');
        Config::$isProduction = config('services.midtrans.isProduction');
        Config::$isSanitized = config('services.midtrans.isSanitized');
        Config::$is3ds = config('services.midtrans.is3Ds');

        // buat instance midtrans notification
        $notification = new Notification();

        //assign variable
        $status = $notification->transaction_status;
        $type = $notification->payment_type;
        $fraud = $notification->fraud_status;
        $order_id = $notification->order_id;

        // cari transaksi berdasarkan ID
        $transaction = Transaction::findOrFail($order_id);

        // handle transaksi
        if ($status == 'capture') {
            if ($type == 'credit_card') {
                $transaction->status = "PENDING";
            } else {
                $transaction->status = "SUCCESS";
            }
        } elseif ($status == 'settlement') {
            $transaction->status = "SUCCESS";
        } elseif ($status == 'pending') {
            $transaction->status = "PENDING";
        } elseif ($status == 'deny') {
            $transaction->status = "CANCELLED";
        } elseif ($status == 'expire') {
            $transaction->status = "CANCELLED";
        } elseif ($status == 'cancel') {
            $transaction->status = "CANCELLED";
        }

        // simpan transaksi
        $transaction->save();
    }

    public function success(Request $request)
    {
        return view('midtrans.success');
    }

    public function unfinished(Request $request)
    {
        return view('midtrans.unfinished');
    }

    public function error(Request $request)
    {
        return view('midtrans.error');
    }
}
