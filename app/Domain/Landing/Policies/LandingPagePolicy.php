<?php

namespace App\Domain\Landing\Policies;

use App\Models\User;

/**
 * Landing pages are content: both administrators and editors manage them.
 * Platform-wide concerns (modules, settings) stay behind their own gates.
 */
class LandingPagePolicy
{
    /**
     * Determine whether the user can list landing pages.
     */
    public function viewAny(User $user): bool
    {
        return $this->managesContent($user);
    }

    /**
     * Determine whether the user can create landing pages.
     */
    public function create(User $user): bool
    {
        return $this->managesContent($user);
    }

    /**
     * Determine whether the user can update a landing page.
     */
    public function update(User $user): bool
    {
        return $this->managesContent($user);
    }

    /**
     * Determine whether the user can delete a landing page.
     */
    public function delete(User $user): bool
    {
        return $this->managesContent($user);
    }

    /**
     * Determine whether the user belongs to a content-managing role.
     */
    private function managesContent(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_EDITOR], true);
    }
}
