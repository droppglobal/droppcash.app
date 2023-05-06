<?php

namespace App\Http\Controllers\User;

use App\Models\Wallet;
use App\Models\InvItem;
use App\Models\Invoice;
use App\Models\Currency;
use App\Models\Transaction;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Escrow;


class ManageInvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::where('user_id',auth()->id())->latest()->paginate(15);
        return view('user.invoice.index',compact('invoices'));
    }

    public function create()
    {
        $currencies = Currency::where('status', 1)->get();
        return view('user.invoice.create',compact('currencies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'invoice_to' => 'required',
            'email'      => 'required|email',
            'address'    => 'required',
            'currency'   => 'required',
            'item'       => 'required',
            'item.*'     => 'required',
            'amount'     => 'required',
            'amount.*'   => 'required|numeric|gt:0'
        ]);

        $charge = charge('create-invoice');
        $currency = Currency::findOrFail($request->currency);
        $allow_escrow = ($request->allow_escrow == 1) ? true : false;
        $amount = array_sum($request->amount);
        $finalCharge = chargeCalc($charge,$amount,$currency->rate);
        $willGetAmount = numFormat($amount - $finalCharge);

        $invoice = new Invoice();
        $invoice->user_id      = auth()->id();
        $invoice->number       = 'INV-'.randNum(8);
        $invoice->invoice_to   = $request->invoice_to;
        $invoice->email        = $request->email;
        $invoice->address      = $request->address;
        $invoice->currency_id  = $currency->id;
        $invoice->charge       = $finalCharge;
        $invoice->allow_escrow = $allow_escrow;
        $invoice->pay_charge = $request->pay_charge;
        $invoice->final_amount = $amount;
        $invoice->get_amount   = $willGetAmount;
        $invoice->save();

        $items = array_combine($request->item,$request->amount);
        foreach($items as $item => $amount){
            $invItem             = new InvItem();
            $invItem->invoice_id = $invoice->id;
            $invItem->name       = $item;
            $invItem->amount	 = $amount;
            $invItem->save();
        }
     
        $route = route('invoice.view',encrypt($invoice->number));
        @email([

            'email'   => $invoice->email,
            "subject" => trans('Invoice Payment'),
            'message' => trans('Hello')." $invoice->invoice_to,<br/></br>".

                trans('You have pending payment of invoice')." <b>$invoice->number</b>.".trans('Please click the below link to complete your payment') .".<br/></br>".
                
                trans('Invoice details').": <br/></br>".
                
                trans('Amount')  .":  $amount $currency->code <br/>".
                trans('Payment Link')." :  <a href='$route' target='_blank'>".trans('Click To Payment')."</a><br/>".
                trans('Time')." : $invoice->created_at,

            " 
        ]);

        return back()->with('success','Invoice has been created');
    }

    public function edit($id)
    {
        $invoice = Invoice::findOrFail($id);
        if($invoice->status == 1){
            return back()->with('error','Sorry! can\'t edit published invoice.');
        }
        $currencies = Currency::where('status', 1)->get();
        return view('user.invoice.edit',compact('invoice','currencies'));
    }

    public function update(Request $request,$id)
    {
        $request->validate([
            'invoice_to' => 'required',
            'email'      => 'required|email',
            'address'    => 'required',
            'currency'   => 'required',
            'item'       => 'required',
            'item.*'     => 'required',
            'amount'     => 'required',
            'amount.*'   => 'required|numeric|gt:0' 
        ],['amount.*.gt'=>'Amount must be greater than 0']);

        $charge = charge('create-invoice');
        $currency = Currency::findOrFail($request->currency);

        $finalCharge = chargeCalc($charge,array_sum($request->amount),$currency->rate);
        $willGetAmount = numFormat(array_sum($request->amount) - $finalCharge);

        $invoice = Invoice::findOrFail($id);
        $invoice->user_id      = auth()->id();
        $invoice->invoice_to   = $request->invoice_to;
        $invoice->email        = $request->email;
        $invoice->address      = $request->address;
        $invoice->currency_id  = $currency->id;
        $invoice->charge       = $finalCharge;
        $invoice->final_amount = array_sum($request->amount);
        $invoice->get_amount   = $willGetAmount;
        $invoice->update();

        $invoice->items()->delete();
        $items = array_combine($request->item,$request->amount);
        foreach($items as $item => $amount){
            $invItem             = new InvItem();
            $invItem->invoice_id = $invoice->id;
            $invItem->name       = $item;
            $invItem->amount	 = $amount;
            $invItem->save();
        }
        return back()->with('success','Invoice has been updated');
    }

    public function payStatus(Request $request)
    {
        $invoice = Invoice::findOrFail($request->id);
        if(!$invoice) return response(['error'=>'Invalid request']);
        
        if($invoice->payment_status == 1){
            $invoice->payment_status = 0;
            $invoice->update();
            return response(['unpaid'=>'Payment status changed to un-paid']);
        }else{
            $invoice->payment_status = 1;
            $invoice->update();
            return response(['paid'=>'Payment status changed to paid']);
        }
        
        
    }
    public function publishStatus(Request $request)
    {
        $invoice = Invoice::findOrFail($request->id);
        if(!$invoice) return response(['error'=>'Invalid request']);
        
        if($invoice->status == 1){
            $invoice->status = 0;
            $invoice->update();
            return response(['unpublish'=>trans('Status changed to un-published')]);
        }else{
            $invoice->status = 1;
            $invoice->update();
            return response(['publish'=>trans('Status changed to published')]);
        }

    }
       
    public function cancel($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->status = 2;
        $invoice->save();
        return redirect(route('user.invoice.index'))->with('success','Invoice has been cancelled');
    }

    public function invoiceView($number)
    {
      
        if(!auth()->user()){
        
            session()->put('invoice_return_url',route('user.invoice.payment',$number));
            // codingeagle https://fiverr.com/codingeagle
        }


        try {
            $invoice = Invoice::where('number',decrypt($number))->firstOrFail();
        } catch (\Throwable $th) {
            return back()->with('error','Something went wrong.');
        }

        if($invoice->status == 0) return back()->with('error','Invoice not published yet.');
        if($invoice->status == 2) return back()->with('error','Invoice has been cancelled.');
        return view('user.invoice.view',compact('invoice'));

    }
    public function view($number)
    {
        $invoice = Invoice::where('number',$number)->firstOrFail();
        
        return view('user.invoice.invoice',compact('invoice'));
    }

    public function sendToMail($id)
    {
        $invoice = Invoice::findOrFail($id);
        $currency = $invoice->currency;
        $amount = amount($invoice->final_amount,$currency->type,3);
        $route = route('invoice.view',encrypt($invoice->number));
     
        @email([

            'email'   => $invoice->email,
            "subject" => trans('Invoice Payment'),
            'message' => trans('Hello')." $invoice->invoice_to,<br/></br>".

                trans('You have pending payment of invoice')." <b>$invoice->number</b>.".trans('Please click the below link to complete your payment') .".<br/></br>".
                
                trans('Invoice details').": <br/></br>".
                
                trans('Amount')  .":  $amount $currency->code <br/>".
                trans('Payment Link')." :  <a href='$route' target='_blank'>".trans('Click To Payment')."</a><br/>".
                trans('Time')." : $invoice->created_at,

            " 
        ]);

        return back()->with('success','Invoice has been sent to the recipient');
    }

    public function invoicePayment($number)
    {

     

        try {
            $invoice = Invoice::where('number',decrypt($number))->firstOrFail();
            if($invoice->payment_status == 1){
                return back()->with('error','Invoice already been paid');
            }
            session()->put('invoice',encrypt($invoice));
        } catch (\Throwable $th) {
           return back()->with('error','Something went wrong');
        }

        if($invoice->user_id == auth()->id()){
            return back()->with('error','You can not pay your own invoice.');
        }

        return view('user.invoice.invoice_payment',compact('invoice'));
    }

    public function invoicePaymentSubmit(Request $request,$number)
    {
        if($request->payment == 'gateway'){
            return redirect(route('user.pay.invoice'));
        }
    
    
        if($request->payment == 'escrow'){
    

            // do escrow 
            $invoice = decrypt(session('invoice'));
            $invoice_id = $invoice->number;

            @$isInvoiceExist = Escrow::where('invoice_number',$invoice_id)->get();
            // fiverr.com/codingeagle
            if(count($isInvoiceExist) > 0){
                return back()->with('error','Page Expired');
            }
            $sender_id = $invoice->user_id;
            // currency of invoice
            $invoice_currency_id = $invoice->currency_id;
            $currency = Currency::where('id',$invoice_currency_id)->first();
            $currency_id = $currency->id;
      
            // personal
            $invoice_to_email = $invoice->email;
            $amount = $invoice->final_amount;

            $receiver_email = $invoice_to_email;
            $receiver = User::where('email',$receiver_email)->first();
            $sender_info = User::where('id',$sender_id)->first();
          
                  // wallet info etc
                  $wallet_info = Wallet::where('user_id',$receiver->id)->where('currency_id',$currency_id)->where('user_type',1)->first();
                  $wallet_id = $wallet_info->id;
                  $senderWallet = Wallet::where('id',$wallet_id)->where('user_type',1)->where('user_id',$receiver->id)->first();
                  $pay_charge = $invoice->pay_charge ?? 0; // true or false 0 or 1
                  // wallet info etc
            $charge = charge('make-escrow');  

            $finalCharge = amount(chargeCalc($charge,$amount,$currency->rate),$currency->type);
            if($pay_charge) $finalAmount =  amount($amount + $finalCharge, $currency->type);
            else  $finalAmount =  amount($amount, $currency->type);
            if($senderWallet->balance < $finalAmount) return back()->with('error','Insufficient balance.');

            // personal
            // currency of invoice

            // make invoice 
       
            $senderWallet->balance -= $finalAmount;
            $senderWallet->update();


    $escrow               = new Escrow();
    $escrow->trnx         = str_rand();
    $escrow->user_id      = $receiver->id;
    $escrow->recipient_id = $sender_id;
    $escrow->description  = "Escrow Payment For Invoice #$invoice_id";
    $escrow->amount       = $amount;
    // $escrow->pay_charge   = $pay_charge ? 1 : 0;
    $escrow->pay_charge   = $pay_charge;
    $escrow->charge       = $finalCharge;
    $escrow->invoice_number = $invoice_id;
    $escrow->currency_id  = $currency->id;
    $escrow->save();

    $trnx              = new Transaction();
    $trnx->trnx        = $escrow->trnx;
    $trnx->user_id     = $receiver->id;
    $trnx->user_type   = 1;
    $trnx->currency_id = $currency->id;
    $trnx->wallet_id   = $senderWallet->id;
    $trnx->amount      = $finalAmount;
    $trnx->charge      = $finalCharge;
    $trnx->remark      = 'make_escrow';
    $trnx->type        = '-';
    $trnx->details     = trans('Made escrow to '). $sender_info->email;
    $trnx->save();
            // make invoice 
            $trans_details = $trnx->details;
if(!empty($invoice->success_url) && isset($invoice->success_url)){
    return redirect($invoice->success_url);
}
else{
    return view('user.invoice.invoice_escrow',compact('trans_details'));

}

      
            // do escrow
        }
    
        elseif($request->payment == 'wallet'){
            try {
                $invoice = decrypt(session('invoice'));
            } catch (\Throwable $th) {
               return back()->with('error','Something went wrong');
            }

            $wallet = Wallet::where('user_id',auth()->id())->where('user_type',1)->where('currency_id',$invoice->currency_id)->first();

            if(!$wallet){
                $wallet =  Wallet::create([
                    'user_id'     => auth()->id(),
                    'user_type'   => 1,
                    'currency_id' => $invoice->currency_id,
                    'balance'     => 0
                ]);
            }

            if($wallet->balance < $invoice->final_amount) {
                return back()->with('error','Insufficient balance to your wallet');
            }


            $wallet->balance -= $invoice->final_amount;
            $wallet->update();

            $trnx              = new Transaction();
            $trnx->trnx        = str_rand();
            $trnx->user_id     = auth()->id();
            $trnx->user_type   = 1;
            $trnx->currency_id = $invoice->currency_id;
            $trnx->wallet_id   = $wallet->id;
            $trnx->amount      = $invoice->final_amount;
            $trnx->charge      = 0;
            $trnx->remark      = 'invoice_payment';
            $trnx->invoice_num = $invoice->number;
            $trnx->type        = '-';
            $trnx->details     = trans('Payment to invoice : '). $invoice->number;
            $trnx->save();

            $rcvWallet = Wallet::where('user_id',$invoice->user_id)->where('user_type',1)->where('currency_id',$invoice->currency_id)->first();
        
            if(!$rcvWallet){
                $rcvWallet =  Wallet::create([
                    'user_id'     => $invoice->user_id,
                    'user_type'   => 1,
                    'currency_id' => $invoice->currency_id,
                    'balance'     => 0
                ]);
            }

            $rcvWallet->balance += $invoice->get_amount;
            $rcvWallet->update();

            $rcvTrnx              = new Transaction();
            $rcvTrnx->trnx        = $trnx->trnx;
            $rcvTrnx->user_id     = $invoice->user_id;
            $rcvTrnx->user_type   = 1;
            $rcvTrnx->currency_id = $invoice->currency_id;
            $rcvTrnx->wallet_id   = $rcvWallet->id;
            $rcvTrnx->amount      = $invoice->get_amount;
            $rcvTrnx->charge      = $invoice->charge;
            $rcvTrnx->remark      = 'invoice_payment';
            $rcvTrnx->invoice_num = $invoice->number;
            $rcvTrnx->type        = '+';
            $rcvTrnx->details     = trans('Receive Payment from invoice : '). $invoice->number;
            $rcvTrnx->save();

            $invoice->payment_status = 1;
            $invoice->update();


            @mailSend('received_invoice_payment',[
                'amount' => amount($invoice->get_amount,$invoice->currency->type,2),
                'curr'   => $invoice->currency->code,
                'trnx'   => $rcvTrnx->trnx,
                'from_user' => $invoice->email,
                'inv_num'  => $invoice->number,
                'after_balance' => amount($rcvWallet->balance,$invoice->currency->type,2),
                'charge' => amount($invoice->charge,$invoice->currency->type,2),
                'date_time' => dateFormat($rcvTrnx->created_at)
            ],$invoice->user);
            
            session()->forget('invoice');
            if(!empty($invoice->success_url) && isset($invoice->success_url)){
                return redirect($invoice->success_url);
            }
            else{
            return redirect(route('user.dashboard'))->with('success','Payment completed');
            }

        }
        else{
            abort(404);
        }
    }
}
