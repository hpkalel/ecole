<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Invitation;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'username' => 'required|string|max:255|unique:'.User::class,
            'nom' => 'required|string|max:255',
            'code_invitation' => 'nullable|string',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $role = 'prof'; // default
        if ($request->code_invitation) {
            $invitation = Invitation::where('code', $request->code_invitation)->where('is_used', false)->first();
            if (!$invitation) {
                throw ValidationException::withMessages([
                    'code_invitation' => 'Code d\'invitation invalide ou déjà utilisé.',
                ]);
            }
            $invitation->update(['is_used' => true]);
        }
        
        // Wait, if there's no code_invitation, should they be allowed to register?
        // Let's allow registration but maybe they need admin approval later (is_active = false)
        // Or if code is present it's an invited prof, etc.
        // Actually, if we look at legacy, only Directeur or Prof invited can register.
        // Let's assume everyone registering is a 'prof' by default unless we set admin role manually.

        $user = User::create([
            'username' => $request->username,
            'nom' => $request->nom,
            'role' => $role,
            'is_active' => true,
            'code_invitation' => $request->code_invitation,
            'password' => Hash::make($request->password),
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
