<?php

namespace App\Domain\Connectors\Policies;

use App\Models\User;

/**
 * Connectors are managed by administrators and editors alike: the
 * credentials are write-only from the UI point of view, so both roles
 * carry the same powers (CRUD + connection test). Platform-wide concerns
 * (modules, settings) stay behind their own gates.
 */
class ConnectorPolicy
{
    /**
     * Determine whether the user can list connectors.
     */
    public function viewAny(User $user): bool
    {
        return $this->managesConnectors($user);
    }

    /**
     * Determine whether the user can create connectors.
     */
    public function create(User $user): bool
    {
        return $this->managesConnectors($user);
    }

    /**
     * Determine whether the user can update a connector.
     */
    public function update(User $user): bool
    {
        return $this->managesConnectors($user);
    }

    /**
     * Determine whether the user can delete a connector.
     */
    public function delete(User $user): bool
    {
        return $this->managesConnectors($user);
    }

    /**
     * Determine whether the user can run the connection test.
     */
    public function test(User $user): bool
    {
        return $this->managesConnectors($user);
    }

    /**
     * Determine whether the user belongs to a connector-managing role.
     */
    private function managesConnectors(User $user): bool
    {
        return in_array($user->role, [User::ROLE_ADMIN, User::ROLE_EDITOR], true);
    }
}
