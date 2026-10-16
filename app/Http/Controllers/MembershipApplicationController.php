<?php

namespace App\Http\Controllers;

use App\Mail\ApplicationReceived;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class MembershipApplicationController extends Controller
{
    public function create(): View
    {
        return view('apply');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'nickname' => ['required', 'string', 'max:255', 'alpha_dash', 'unique:users,nickname'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date', 'before:today'],
            'gender' => ['required', 'in:'.implode(',', array_keys(User::GENDERS))],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $applicant = User::create([
            'name' => $data['name'],
            'nickname' => $data['nickname'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'address' => $data['address'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'],
            'password' => $data['password'],
            'role' => 'member',
            'status' => 'pending',
        ]);

        Mail::to($applicant)->send(new ApplicationReceived($applicant));

        return redirect()
            ->route('apply')
            ->with('applied', true);
    }
}
