<?php

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;

class LeadPolicy
{
    /**
     * Admin/staff can view any lead; an agent/agency can only view leads assigned to them.
     */
    public function view(User $user, Lead $lead): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin']) || $user->id === $lead->assigned_to;
    }

    /**
     * Same rule for updating status/notes/tasks on a lead.
     */
    public function update(User $user, Lead $lead): bool
    {
        return $this->view($user, $lead);
    }

    /**
     * Only admins/staff can (re)assign a lead to an agent.
     */
    public function assign(User $user): bool
    {
        return $user->hasAnyRole(['admin', 'super-admin']);
    }
}
