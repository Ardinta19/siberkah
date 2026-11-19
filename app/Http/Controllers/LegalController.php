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
    public function syarat_sewa()
    {
        return view('legal.syarat-sewa');
    }

    /**
     * Show privacy policy.
     *
     * @return View
     */
    public function kebijakan_privasi()
    {
        return view('legal.kebijakan-privasi');
    }

    /**
     * Show contact page.
     *
     * @return View
     */
    public function kontak()
    {
        return view('legal.kontak');
    }
}