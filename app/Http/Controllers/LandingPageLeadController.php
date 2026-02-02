<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LandingPageLeadController extends Controller
{
    public function index($id)
    {
        $page = \App\Models\LandingPage::where('tenant_id', auth()->user()->tenant_id)->findOrFail($id);
        $leads = DB::table('landing_page_leads')
                    ->where('landing_page_id', $id)
                    ->orderBy('created_at', 'desc')
                    ->paginate(20);

        return view('ngo.landing_pages.leads', compact('page', 'leads'));
    }
}
