<?php

namespace App\Http\Controllers;

class PagesController extends Controller
{
    public function contact()
    {
        return view('pages.contact');
    }

    public function privacyPolicy()
    {
        return view('pages.privacy-policy');
    }

    public function shippingPolicy()
    {
        return view('pages.shipping-policy');
    }

    public function refundPolicy()
    {
        return view('pages.refund-policy');
    }

    public function returnsExchanges()
    {
        return view('pages.returns-exchanges');
    }

    public function sizeGuide()
    {
        return view('pages.size-guide');
    }
}
