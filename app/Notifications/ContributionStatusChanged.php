<?php

namespace App\Notifications;

use App\Models\HeritageShopContribution;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ContributionStatusChanged extends Notification
{
    use Queueable;

    public function __construct(public HeritageShopContribution $contribution) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $status = $this->contribution->status;

        return [
            'title' => match ($status) {
                HeritageShopContribution::STATUS_APPROVED => 'Contribution approved',
                HeritageShopContribution::STATUS_REJECTED => 'Contribution rejected',
                HeritageShopContribution::STATUS_REVISION_REQUIRED => 'Revision requested',
                HeritageShopContribution::STATUS_DELETED => 'Contribution removed',
                default => 'Contribution status updated',
            },
            'message' => match ($status) {
                HeritageShopContribution::STATUS_APPROVED => 'Your heritage shop contribution has been approved.',
                HeritageShopContribution::STATUS_REJECTED => 'Your heritage shop contribution was not approved. Open it to read the feedback.',
                HeritageShopContribution::STATUS_REVISION_REQUIRED => 'An administrator requested changes to your contribution.',
                HeritageShopContribution::STATUS_DELETED => 'An administrator removed this contribution. Open it to read the reason.',
                default => 'The status of your heritage shop contribution has changed.',
            },
            'contribution_id' => $this->contribution->id,
            'status' => $status,
        ];
    }
}
