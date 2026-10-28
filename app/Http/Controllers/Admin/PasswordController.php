<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PasswordUpdateRequest;
use Illuminate\Http\RedirectResponse;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(PasswordUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            // The "auth" middleware guarantees a user on this route; the
            // guard only satisfies the nullable signature.
            return redirect()->route('login');
        }

        $user->update([
            'password' => $request->validated('password'),
        ]);

        return redirect()
            ->route('admin.profile.edit')
            ->with('status', __('Password updated.'));
    }
}
