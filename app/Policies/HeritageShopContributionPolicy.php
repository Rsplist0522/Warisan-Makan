<?php

namespace App\Policies;

use App\Models\HeritageShopContribution;
use App\Models\User;

class HeritageShopContributionPolicy
{
    public function view(User $user, HeritageShopContribution $contribution): bool
    {
        return (int) $contribution->user_id === (int) $user->id;
    }

    public function update(User $user, HeritageShopContribution $contribution): bool
    {
        return $contribution->canBeEditedBy($user);
    }

    public function deleteDraft(User $user, HeritageShopContribution $contribution): bool
    {
        return (int) $contribution->user_id === (int) $user->id
            && $contribution->status === HeritageShopContribution::STATUS_DRAFT;
    }

    public function submitDraft(User $user, HeritageShopContribution $contribution): bool
    {
        return (int) $contribution->user_id === (int) $user->id
            && $contribution->status === HeritageShopContribution::STATUS_DRAFT;
    }
}
