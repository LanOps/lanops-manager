<?php

namespace App\Http\Controllers\Admin;

use Auth;
use Session;

use App\Exceptions\RefundException;
use App\Models\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PurchasesController extends Controller
{
    /**
     * Show All Purchases Index Page
     * @return View
     */
    public function index()
    {
        return view('admin.purchases.index')
            ->withPurchases(Purchase::orderBy('id', 'DESC')->paginate(20));
    }

    /**
     * Show Purchase Page
     * @param Purchase $purchase
     * @return View
     */
    public function show(Purchase $purchase)
    {
        return view('admin.purchases.show')
            ->withPurchase($purchase);
    }

    /**
     * Refund a purchase and/or cancel its tickets
     * @param Purchase $purchase
     * @param Request $request
     * @return Redirect
     */
    public function refund(Purchase $purchase, Request $request)
    {
        $this->validate($request, [
            'participants'   => 'nullable|array',
            'participants.*' => 'integer',
            'amount'         => 'required|numeric|min:0|max:100000',
            'reason'         => 'nullable|string|max:255',
        ]);

        try {
            $refund = $purchase->refund(
                $request->input('participants', []),
                (int) round($request->amount * 100),
                $request->boolean('cancel_tickets'),
                Auth::user(),
                $request->reason
            );
        } catch (RefundException $e) {
            Session::flash('alert-danger', 'Refund not made: ' . $e->getMessage());
            return redirect('/admin/purchases/' . $purchase->id);
        }

        $message = [];
        if ($refund->amount > 0) {
            $amount = config('app.currency_symbol') . number_format((float) $refund->amount, 2);
            $message[] = $refund->wentThroughGateway()
                ? $amount . ' refunded to the card through Stripe (' . $refund->gateway_refund_id . ').'
                : $amount . ' recorded as refunded. Send the money back by hand, the site has not sent it.';
        }
        if ($count = $refund->participants()->count()) {
            $message[] = $count . ' ' . ($count == 1 ? 'ticket' : 'tickets') . ' cancelled.';
        }
        Session::flash('alert-success', implode(' ', $message));
        return redirect('/admin/purchases/' . $purchase->id);
    }
}
