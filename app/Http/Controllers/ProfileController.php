<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use App\Models\User;

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
     * Update the user's profile information (name + avatar).
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->name = $request->validated()['name'];

        if ($request->hasFile('avatar') && $request->file('avatar')->isValid()) {
            $file = $request->file('avatar');
            $disk = config('filesystems.default') === 'gcs' ? 'gcs' : 'public';

            // Delete old avatar file if it exists
            if ($user->avatar) {
                $oldPath = ltrim(parse_url($user->avatar, PHP_URL_PATH), '/');
                // Strip /storage/ prefix for local disk
                $oldPath = preg_replace('#^storage/#', '', $oldPath);
                Storage::disk($disk)->delete($oldPath);
            }

            $path = $file->store('avatars', $disk);

            if ($disk === 'gcs') {
                $bucket = config('filesystems.disks.gcs.bucket');
                $prefix = ltrim(config('filesystems.disks.gcs.path_prefix', ''), '/');
                $filePath = $prefix ? "{$prefix}/{$path}" : $path;
                $user->avatar = 'https://storage.googleapis.com/' . $bucket . '/' . $filePath;
            } else {
                $user->avatar = '/storage/' . $path;
            }
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's email address.
     */
    public function updateEmail(Request $request): RedirectResponse
    {
        $request->validateWithBag('updateEmail', [
            'email'              => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($request->user()->id)],
            'email_confirmation' => ['required', 'same:email'],
            'password'           => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $user->email = $request->input('email');
        $user->email_verified_at = null;
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'email-updated');
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
