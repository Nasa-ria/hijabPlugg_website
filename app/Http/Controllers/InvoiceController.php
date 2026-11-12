<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

/**
 * Invoices / payment receipts for orders. One template, shown as a printable page
 * or downloaded as a PDF. Amounts are GHS, the currency the customer was charged in.
 *
 *  - Customers: their own paid orders only.
 *  - Admins (orders permission): any order; unpaid orders are marked as such.
 */
class InvoiceController extends Controller
{
    public function show(Request $request, Order $order)
    {
        $this->authorizeInvoice($request, $order);

        return view('invoices.show', $this->data($request, $order, pdf: false));
    }

    public function download(Request $request, Order $order)
    {
        $this->authorizeInvoice($request, $order);

        return Pdf::loadView('invoices.show', $this->data($request, $order, pdf: true))
            ->setPaper('a4')
            ->download($order->invoiceNumber() . '.pdf');
    }

    private function authorizeInvoice(Request $request, Order $order): void
    {
        $user = $request->user();

        // Admin routes are already limited to admins with the "orders" permission
        if ($request->routeIs('admin.*')) {
            return;
        }

        abort_unless($user && $user->id === $order->user_id, 403);
        abort_unless($order->payment_status === 'paid', 404, 'The receipt is available once the order is paid.');
    }

    private function data(Request $request, Order $order, bool $pdf): array
    {
        $order->loadMissing(['items.product', 'items.variant', 'user', 'payment', 'returns']);

        return [
            'order' => $order,
            'pdf' => $pdf,
            'admin' => $request->routeIs('admin.*'),
            'downloadUrl' => $request->routeIs('admin.*')
                ? route('admin.orders.invoice.download', $order)
                : route('orders.invoice.download', $order),
            'backUrl' => $request->routeIs('admin.*') ? route('admin.orders.show', $order) : route('orders.show', $order),
            'store' => [
                'name' => config('app.name', 'Nasaari Store'),
                'email' => config('nasaari.contact.email'),
                'phone' => config('nasaari.contact.phone'),
                'address' => trim(collect([config('shipping.origin.address'), config('shipping.origin.city'), \App\Support\Countries::name(config('shipping.origin.country'))])->filter()->implode(', ')),
                'website' => preg_replace('#^https?://#', '', rtrim((string) config('app.url'), '/')),
            ],
        ];
    }
}
