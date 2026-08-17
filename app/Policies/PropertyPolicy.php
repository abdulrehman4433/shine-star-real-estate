<?php

namespace App\Policies;

use App\Models\Property;
use App\Models\User;

class PropertyPolicy
{
    /**
     * Anyone (including guests) can view an approved property.
     * The owner or an admin/staff member can also view it regardless of status.
     */
    public function view(?User $user, Property $property): bool
    {
        if ($property->isApproved()) {
            return true;
        }

        return $user && ($user->id === $property->user_id || $user->hasAnyRole(['admin', 'super-admin']));
    }

    /**
     * Any authenticated member (guest, user, agent, agency, admin) may list a property.
     * Their listing goes through moderation unless created by an admin/staff member.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * The owner or an admin/staff member can edit a listing.
     */
    public function update(User $user, Property $property): bool
    {
        return $user->id === $property->user_id || $user->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * The owner or an admin/staff member can delete a listing.
     */
    public function delete(User $user, Property $property): bool
    {
        return $user->id === $property->user_id || $user->hasAnyRole(['admin', 'super-admin']);
    }

    /**
     * Only admins/staff can approve, reject, feature, or expire a listing.
     */
    public function moderate(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin']);
    }
}
