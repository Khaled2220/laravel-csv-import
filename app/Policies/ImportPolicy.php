<?php

namespace App\Policies;

use App\Models\Import;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ImportPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Import $import): bool
    {
        return $import->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Import $import): bool
    {
        return $import->user_id ===$user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Import $import): bool
    {
        return $import->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Import $import): bool
    {
        return $import->user_id ===$user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Import $import): bool
    {
        return $import->user_id === $user->id;
    }

    /**
     * Determine whether the user can cancel the import.
     */
    public function cancel(User $user , Import $import) : bool
    {
        return $import->user_id === $user->id && in_array($import->status,[
            'pending',
            'processing',
        ],true);
    }

    /**
     * Determine whether the user can retry the import.
     */
    public function retry(User $user , Import $import) : bool
    {
        return $import->user_id === $user->id && $import->status ==='failed';
    }
}
