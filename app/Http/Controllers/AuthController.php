<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Support\DatabaseColumn;

class AuthController extends Controller
{
    public function loginForm()
    {
        if (session('glowskin_user')) {
            return redirect()->route('home');
        }

        return view('auth.login', [
            'settings' => SiteSetting::pluck('value', 'key'),
        ]);
    }

    public function registerForm()
    {
        if (session('glowskin_user')) {
            return redirect()->route('home');
        }

        return view('auth.register', [
            'settings' => SiteSetting::pluck('value', 'key'),
        ]);
    }

    public function forgotPasswordForm()
    {
        if (session('glowskin_user')) {
            return redirect()->route('home');
        }

        return view('auth.forgot-password', [
            'settings' => SiteSetting::pluck('value', 'key'),
        ]);
    }

    public function sendResetCode(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        $user = DB::table('users')->where('email', $data['email'])->first();

        if (!$user) {
            return response()->json([
                'ok' => false,
                'message' => 'Email tidak ditemukan di akun GlowSkin.',
            ], 422);
        }

        $code = (string) random_int(100000, 999999);

        session([
            'glowskin_reset_email' => $data['email'],
            'glowskin_reset_code' => Hash::make($code),
            'glowskin_reset_code_expired_at' => now()->addMinutes(10)->timestamp,
        ]);

        try {
            Mail::raw(
                "Kode reset password GlowSkin kamu adalah: {$code}\n\nKode ini berlaku selama 10 menit.\nJangan bagikan kode ini kepada siapa pun.",
                function ($message) use ($data) {
                    $message->to($data['email'])
                        ->subject('Kode Reset Password GlowSkin');
                }
            );
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Gagal mengirim kode reset. Cek konfigurasi MAIL di file .env.',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Kode reset password berhasil dikirim ke email kamu.',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'reset_code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = DB::table('users')->where('email', $data['email'])->first();

        if (!$user) {
            return back()
                ->withInput($request->except('password', 'password_confirmation', 'reset_code'))
                ->withErrors(['email' => 'Email tidak ditemukan di akun GlowSkin.']);
        }

        $sessionEmail = session('glowskin_reset_email');
        $sessionCodeHash = session('glowskin_reset_code');
        $expiredAt = (int) session('glowskin_reset_code_expired_at', 0);

        if (!$sessionEmail || !$sessionCodeHash || $expiredAt < now()->timestamp) {
            return back()
                ->withInput($request->except('password', 'password_confirmation', 'reset_code'))
                ->withErrors(['reset_code' => 'Kode reset sudah habis atau belum dikirim. Klik tombol Kirim Kode lagi.']);
        }

        if (strtolower($sessionEmail) !== strtolower($data['email'])) {
            return back()
                ->withInput($request->except('password', 'password_confirmation', 'reset_code'))
                ->withErrors(['email' => 'Email harus sama dengan email yang menerima kode reset.']);
        }

        if (!Hash::check($data['reset_code'], $sessionCodeHash)) {
            return back()
                ->withInput($request->except('password', 'password_confirmation', 'reset_code'))
                ->withErrors(['reset_code' => 'Kode reset password salah.']);
        }

        DB::table('users')->where('id', $user->id)->update([
            'password' => Hash::make($data['password']),
            'updated_at' => now(),
        ]);

        session()->forget([
            'glowskin_reset_email',
            'glowskin_reset_code',
            'glowskin_reset_code_expired_at',
        ]);

        return redirect()
            ->route('login')
            ->with('success', 'Password berhasil direset. Silakan login memakai password baru.');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = DB::table('users')->where('email', $credentials['email'])->first();

        if (!$user || !Hash::check($credentials['password'], $user->password)) {
            return back()
                ->withInput($request->only('email'))
                ->withErrors(['email' => 'Email atau password tidak sesuai.']);
        }

        session([
            'glowskin_user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'user',
            ],
        ]);

        return redirect()->route(($user->role ?? 'user') === 'admin' ? 'admin.dashboard' : 'home');
    }

    public function sendRegisterCode(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);

        if (DB::table('users')->where('email', $data['email'])->exists()) {
            return response()->json([
                'ok' => false,
                'message' => 'Email ini sudah terdaftar. Silakan login.',
            ], 422);
        }

        $code = (string) random_int(100000, 999999);

        session([
            'glowskin_register_email' => $data['email'],
            'glowskin_register_code' => Hash::make($code),
            'glowskin_register_code_expired_at' => now()->addMinutes(10)->timestamp,
        ]);

        try {
            Mail::raw(
                "Kode verifikasi GlowSkin kamu adalah: {$code}\n\nKode ini berlaku selama 10 menit.\nJangan bagikan kode ini kepada siapa pun.",
                function ($message) use ($data) {
                    $message->to($data['email'])
                        ->subject('Kode Verifikasi Register GlowSkin');
                }
            );
        } catch (\Throwable $e) {
            return response()->json([
                'ok' => false,
                'message' => 'Gagal mengirim kode email. Cek konfigurasi MAIL di file .env.',
            ], 500);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Kode verifikasi berhasil dikirim ke email kamu.',
        ]);
    }

    public function register(Request $request)
    {
        $rules = [
            'email' => ['required', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'verification_code' => ['required', 'digits:6'],
            'terms' => ['accepted'],
        ];

        $data = $request->validate($rules);

        if (DB::table('users')->where('email', $data['email'])->exists()) {
            return back()
                ->withInput($request->except('password', 'password_confirmation', 'verification_code'))
                ->withErrors(['email' => 'Email ini sudah terdaftar.']);
        }

        $sessionEmail = session('glowskin_register_email');
        $sessionCodeHash = session('glowskin_register_code');
        $expiredAt = (int) session('glowskin_register_code_expired_at', 0);

        if (!$sessionEmail || !$sessionCodeHash || $expiredAt < now()->timestamp) {
            return back()
                ->withInput($request->except('password', 'password_confirmation', 'verification_code'))
                ->withErrors(['verification_code' => 'Kode verifikasi sudah habis atau belum dikirim. Klik tombol Verif Email lagi.']);
        }

        if (strtolower($sessionEmail) !== strtolower($data['email'])) {
            return back()
                ->withInput($request->except('password', 'password_confirmation', 'verification_code'))
                ->withErrors(['email' => 'Email harus sama dengan email yang menerima kode verifikasi.']);
        }

        if (!Hash::check($data['verification_code'], $sessionCodeHash)) {
            return back()
                ->withInput($request->except('password', 'password_confirmation', 'verification_code'))
                ->withErrors(['verification_code' => 'Kode verifikasi salah.']);
        }

        $payload = [
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'user',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        if (DatabaseColumn::exists('users', 'phone')) {
            $payload['phone'] = $data['phone'] ?? null;
        }

        if (DatabaseColumn::exists('users', 'email_verified_at')) {
            $payload['email_verified_at'] = now();
        }

        $id = DB::table('users')->insertGetId($payload);
        $user = DB::table('users')->where('id', $id)->first();

        session()->forget([
            'glowskin_register_email',
            'glowskin_register_code',
            'glowskin_register_code_expired_at',
        ]);

        session([
            'glowskin_user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ?? 'user',
            ],
        ]);

        return redirect()->route('home')->with('success', 'Register berhasil. Email kamu sudah diverifikasi.');
    }

    public function logout(Request $request)
    {
        session()->forget('glowskin_user');

        return redirect()->route('home');
    }
}
