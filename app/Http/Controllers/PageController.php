<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

/**
 * Halaman informasi publik. Kontennya dibaca dari config/tkj.php.
 */
class PageController extends Controller
{
    public function home(): View
    {
        return view('pages.home');
    }

    public function profile(): View
    {
        return view('pages.profile');
    }

    public function competencies(): View
    {
        return view('pages.competencies');
    }

    public function organization(): View
    {
        return view('pages.organization');
    }

    public function itTeam(): View
    {
        return view('pages.it-team');
    }

    public function topology(): View
    {
        return view('pages.topology');
    }

    public function gallery(): View
    {
        return view('pages.gallery');
    }

    public function facilities(): View
    {
        return view('pages.facilities');
    }

    public function contact(): View
    {
        return view('pages.contact');
    }
}
