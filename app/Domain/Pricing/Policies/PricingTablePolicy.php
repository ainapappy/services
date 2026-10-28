<?php

namespace App\Domain\Pricing\Policies;

use App\Models\User;

/**
 * Pricing tables are content: both administrators and editors manage them.
 * Platform-wide concerns (modules, settings) stay behind their own gates.
 */
class PricingTablePolicy
{
    /**
     * Determine whether the user can list pricing tables.
     */
    public function viewAny(User $user): bool
    {
        return $this->managesContent($user);
    }

    /**
     * Determine whether the user can create pricing tables.
     */
    public function create(User $user): bool
    {
        return $this->managesContent($user);
    }

    /**
     * Determine whether the user can update a pricing table.
     */
    public function update(User $user): bool
    {
        return $this->managesContent($user);
    }

    /**
     * Determine whether the user can delete a pricing table.
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
