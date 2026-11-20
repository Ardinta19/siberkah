<?php

namespace App\Http\Controllers;

/**
 * Legal Controller - Terms and policies pages
 * --------------------------------------------------------
 * guest:customer middleware applied at route level.
 */
class LegalController extends \Illuminate\Routing\Controller
{
    /**
     * Show terms of service.
     *
     * @return View
     */
    public function syaratSewa()
    {
        return view('publik.legal.syarat-sewa');
    }

    /**
     * Show privacy policy.
     *
     * @return View
     */
    public function kebijakanPrivasi()
    {
        return view('publik.legal.kebijakan-privasi');
    }

    /**
     * Show contact page.
     *
     * @return View
     */
    public function kontak()
    {
        return view('publik.legal.kontak');
    }
}