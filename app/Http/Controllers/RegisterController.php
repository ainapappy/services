<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

/**
 * Public registration is disabled: accounts are created by administrators
 * (admin:create) or through the back-office once phase 03 delivers it.
 */
class RegisterController extends Controller
{
    /**
     * Redirect the registration screen to the login screen with a clear message.
     */
    public function create(): RedirectResponse
    {
        return redirect()
            ->route('login')
            ->with('status', __('Public registration is disabled. Ask an administrator to create an account for you.'));
    }

    /**
     * Reject registration attempts with the same explicit message.
     */
    public function store(): RedirectResponse
    {
        return $this->create();
    }
}
