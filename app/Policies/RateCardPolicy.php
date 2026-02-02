<?php

namespace App\Policies;

use App\Models\RateCard;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class RateCardPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any rate cards.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view_rate_cards');
    }

    /**
     * Determine whether the user can view the rate card.
     */
    public function view(User $user, RateCard $rateCard): bool
    {
        return $user->can('view_rate_cards');
    }

    /**
     * Determine whether the user can create rate cards.
     */
    public function create(User $user): bool
    {
        return $user->can('create_rate_cards');
    }

    /**
     * Determine whether the user can update the rate card.
     */
    public function update(User $user, RateCard $rateCard): bool
    {
        return $user->can('edit_rate_cards');
    }

    /**
     * Determine whether the user can delete the rate card.
     */
    public function delete(User $user, RateCard $rateCard): bool
    {
        // Cannot delete if rate card is being used in any commission calculations
        // This would be checked in the service layer
        return $user->can('delete_rate_cards');
    }

    /**
     * Determine whether the user can restore the rate card.
     */
    public function restore(User $user, RateCard $rateCard): bool
    {
        return $user->can('edit_rate_cards');
    }

    /**
     * Determine whether the user can permanently delete the rate card.
     */
    public function forceDelete(User $user, RateCard $rateCard): bool
    {
        return $user->can('delete_rate_cards') && $user->hasRole('admin');
    }

    /**
     * Determine whether the user can export rate cards.
     */
    public function export(User $user): bool
    {
        return $user->can('view_rate_cards');
    }
}
