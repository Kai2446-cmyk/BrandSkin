<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\DB;

class SkinAnalyzerController extends Controller
{
    public function index()
    {
        return view('skin-analyzer.index');
    }

    public function start()
    {
        return view('skin-analyzer.start');
    }

    public function report()
    {
        return view('skin-analyzer.report');
    }

    public function recommendations()
    {
        return view('skin-analyzer.recommendations');
    }

    public function diary()
    {
        $authUser = session('glowskin_user');
        if (!$authUser || empty($authUser['id'])) {
            return redirect()->route('login')->withErrors(['email' => 'Silakan login terlebih dahulu.']);
        }

        $user = DB::table('users')->where('id', $authUser['id'])->first();
        if (!$user) {
            return redirect()->route('login');
        }

        return view('profile.skin-diary', [
            'user' => $user,
            'settings' => SiteSetting::pluck('value', 'key'),
        ]);
    }
}
