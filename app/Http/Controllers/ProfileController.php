<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile dashboard.
     */
    public function show(Request $request): View
    {
        $user     = $request->user();
        $printify = $user->printifyConnection;

        // Images generated = assistant messages with an image across all user chats
        $imagesGenerated = \App\Models\Message::where('role', 'assistant')
            ->whereNotNull('image')
            ->whereHas('chat', fn ($q) => $q->where('user_id', $user->id))
            ->count();

        // Most used model
        $mostUsedModel = \App\Models\Message::where('role', 'assistant')
            ->whereNotNull('model')
            ->whereHas('chat', fn ($q) => $q->where('user_id', $user->id))
            ->selectRaw('model, count(*) as total')
            ->groupBy('model')
            ->orderByDesc('total')
            ->value('model');

        $stats = [
            'tokens_used'      => $user->tokens_used ?? 0,
            'images_generated' => $imagesGenerated,
            'most_used_model'  => $mostUsedModel ?? '—',
            'products_pushed'  => $printify?->products_pushed ?? 0,
        ];

        return view('profile.show', compact('user', 'printify', 'stats'));
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
