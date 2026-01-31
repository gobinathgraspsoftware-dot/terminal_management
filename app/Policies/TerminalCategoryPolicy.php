<?php

namespace App\Policies;

use App\Models\TerminalCategory;
use App\Models\User;

/**
 * Terminal Category Policy
 *
 * Authorization rules for terminal category operations
 */
class TerminalCategoryPolicy
{
    /**
     * Determine if user can view any categories.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasAnyPermission([
            'view_categories',
            'view_models',
        ]);
    }

    /**
     * Determine if user can view the category.
     */
    public function view(User $user, ?TerminalCategory $category = null): bool
    {
        return $user->hasAnyPermission([
            'view_categories',
            'view_models',
        ]);
    }

    /**
     * Determine if user can create categories.
     */
    public function create(User $user): bool
    {
        return $user->hasPermissionTo('create_categories');
    }

    /**
     * Determine if user can update the category.
     */
    public function update(User $user, ?TerminalCategory $category = null): bool
    {
        return $user->hasPermissionTo('edit_categories');
    }

    /**
     * Determine if user can delete the category.
     */
    public function delete(User $user, TerminalCategory $category): bool
    {
        // Cannot delete if has associated models
        if ($category->terminalModels()->count() > 0) {
            return false;
        }

        return $user->hasPermissionTo('delete_categories');
    }

    /**
     * Determine if user can restore the category.
     */
    public function restore(User $user, TerminalCategory $category): bool
    {
        return $user->hasPermissionTo('delete_categories');
    }

    /**
     * Determine if user can permanently delete the category.
     */
    public function forceDelete(User $user, TerminalCategory $category): bool
    {
        return $user->hasRole('admin');
    }
}
