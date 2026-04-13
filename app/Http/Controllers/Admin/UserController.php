<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('activeSubscription')->where('is_admin', false);

        if ($request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%");
            });
        }

        return Inertia::render('Admin/Users/Index', [
            'users' => $query->latest()->paginate(10)->withQueryString(),
            'filters' => $request->only('search'),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'duration_days' => 'required|integer|min:1',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        Subscription::create([
            'user_id' => $user->id,
            'expires_at' => now()->addDays($request->duration_days),
        ]);

        return back()->with('success', 'Pengguna premium berhasil dibuat.');
    }

    public function update(Request $request, User $user)
    {
        $request->validate([
            'duration_days' => 'required|integer|min:0',
            'password' => 'nullable|string|min:8',
        ]);

        if ($request->password) {
            $user->update([
                'password' => Hash::make($request->password),
            ]);
        }

        if ($request->duration_days > 0) {
            $currentExpiry = $user->activeSubscription ? $user->activeSubscription->expires_at : now();
            if ($currentExpiry < now()) {
                $currentExpiry = now();
            }

            Subscription::create([
                'user_id' => $user->id,
                'expires_at' => \Illuminate\Support\Carbon::parse($currentExpiry)->addDays($request->duration_days),
            ]);
        }

        return back()->with('success', 'Data pengguna berhasil diperbarui.');
    }

    public function grantPremium(Request $request, User $user)
    {
        $request->validate([
            'days' => 'required|integer|min:1',
        ]);

        $expiresAt = now()->addDays($request->days);
        
        // Use updateOrCreate to handle existing subscriptions securely
        Subscription::updateOrCreate(
            ['user_id' => $user->id],
            ['expires_at' => $expiresAt]
        );

        return back()->with('success', "Status premium diberikan kepada {$user->name} selama {$request->days} hari.");
    }

    public function destroy(User $user)
    {
        $user->delete();
        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
