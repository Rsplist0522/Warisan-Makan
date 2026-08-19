<?php

namespace App\Notifications;

use App\Models\CorrectionRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CorrectionRequestStatusChanged extends Notification
{
    use Queueable;

    public function __construct(public CorrectionRequest $correctionRequest) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $status = $this->correctionRequest->status;

        return [
            'title' => match ($status) {
                CorrectionRequest::STATUS_APPROVED => 'Correction request approved',
                CorrectionRequest::STATUS_REJECTED => 'Correction request rejected',
                CorrectionRequest::STATUS_NEEDS_INFORMATION => 'Additional information required',
                default => 'Correction request updated',
            },
            'message' => match ($status) {
                CorrectionRequest::STATUS_APPROVED => 'Your correction request has been approved and the heritage shop profile was updated where appropriate.',
                CorrectionRequest::STATUS_REJECTED => 'Your correction request was not approved. Open it to read the administrator comment.',
                CorrectionRequest::STATUS_NEEDS_INFORMATION => 'An administrator needs more information before deciding on your correction request.',
                default => 'The status of your correction request has changed.',
            },
            'correction_request_id' => $this->correctionRequest->id,
            'correction_request_public_id' => $this->correctionRequest->public_id,
            'heritage_shop_id' => $this->correctionRequest->heritage_shop_id,
            'status' => $status,
        ];
    }
}
