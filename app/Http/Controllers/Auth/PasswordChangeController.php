<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class PasswordChangeController extends Controller
{
    public function show()
    {
        return view('auth.passwords.change');
    }

    public function update(Request $request)
    {
        $request->validate([
            'current_password'      => ['required'],
            'password'              => ['required', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (! Hash::check($request->input('current_password'), $user->password)) {
            return back()->withErrors(['current_password' => 'The current password is incorrect.']);
        }

        if ($request->input('current_password') === $request->input('password')) {
            return back()->withErrors(['password' => 'New password must differ from your current password.']);
        }

        $user->update([
            'password'             => Hash::make($request->input('password')),
            'must_change_password' => false,
        ]);

        return redirect()->route('portal.dashboard')
            ->with('success', 'Password updated successfully.');
    }
}
