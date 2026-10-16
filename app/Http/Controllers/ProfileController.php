<?php

namespace App\Http\Controllers;

use App\Support\AvatarResizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request, AvatarResizer $resizer): RedirectResponse
    {
        $user = $request->user();

        $rules = [
            'phone' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:8192'],
        ];

        if ($request->filled('password')) {
            $rules['current_password'] = ['required', 'current_password'];
        }

        $data = $request->validate($rules);

        $user->phone = $data['phone'] ?? null;
        $user->address = $data['address'] ?? null;

        if (filled($data['password'] ?? null)) {
            $user->password = $data['password'];
        }

        if ($request->hasFile('avatar')) {
            $user->avatar_url = $resizer->store($request->file('avatar'), $user);
        }

        $user->save();

        return redirect()
            ->route('profile.edit')
            ->with('saved', true);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('leaderboard');
    }
}
