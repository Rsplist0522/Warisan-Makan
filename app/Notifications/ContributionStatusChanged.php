<?php

namespace App\Notifications;

use App\Models\HeritageShopContribution;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ContributionStatusChanged extends Notification
{
    use Queueable;

    public function __construct(public HeritageShopContribution $contribution) {}

    public function via(object $notifiable): array
    {
        if (in_array($this->contribution->status, [
            HeritageShopContribution::STATUS_APPROVED,
            HeritageShopContribution::STATUS_REJECTED,
            HeritageShopContribution::STATUS_REVISION_REQUIRED,
        ], true)) {
            return ['database', 'mail'];
        }

        return ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->contribution->status;
        $title = $this->contributionTitle();
        $mail = (new MailMessage)
            ->subject($this->mailSubject())
            ->greeting('Hello '.$notifiable->name.',');

        if ($status === HeritageShopContribution::STATUS_APPROVED) {
            return $mail
                ->line('Your contribution "'.$title.'" has been approved.')
                ->line('You can now view the approved contribution in WarisanMakan.')
                ->action('View Contribution', $this->contributionUrl())
                ->line('Thank you for contributing to WarisanMakan.');
        }

        if ($status === HeritageShopContribution::STATUS_REJECTED) {
            $mail
                ->line('Your contribution "'.$title.'" was not approved.');

            if (filled($this->feedback())) {
                $mail
                    ->line('Reason / Administrator Feedback:')
                    ->line($this->feedback());
            }

            return $mail
                ->action('View Contribution', $this->contributionUrl())
                ->line('Thank you for contributing to WarisanMakan.');
        }

        $mail
            ->line('Your contribution "'.$title.'" requires revision before it can be approved.');

        if (filled($this->feedback())) {
            $mail
                ->line('Administrator Feedback:')
                ->line($this->feedback());
        }

        return $mail
            ->action('Review and Resubmit Contribution', $this->revisionUrl())
            ->line('Please update the requested information and resubmit your contribution.');
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
            'contribution_public_id' => $this->contribution->public_id,
            'url' => $this->contributionUrl(),
            'status' => $status,
        ];
    }

    private function mailSubject(): string
    {
        return match ($this->contribution->status) {
            HeritageShopContribution::STATUS_APPROVED => 'WarisanMakan Contribution Approved',
            HeritageShopContribution::STATUS_REJECTED => 'WarisanMakan Contribution Update - Rejected',
            HeritageShopContribution::STATUS_REVISION_REQUIRED => 'WarisanMakan Contribution Requires Revision',
            default => 'WarisanMakan Contribution Update',
        };
    }

    private function contributionTitle(): string
    {
        return $this->contribution->contribution_title
            ?: $this->contribution->shop_name
            ?: 'heritage shop contribution';
    }

    private function contributionUrl(): string
    {
        return route('community-contribution.contributions.show', $this->contribution);
    }

    private function revisionUrl(): string
    {
        return route('community-contribution.edit', $this->contribution);
    }

    private function feedback(): ?string
    {
        return $this->contribution->admin_feedback ?: $this->contribution->rejection_reason;
    }
}
