<?php

namespace App\Http\Controllers;

use App\Models\ContractInstallment;
use App\Models\User;
use App\Notifications\InstallmentAlert;
use App\Services\AuditService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class InstallmentController extends Controller
{
    /**
     * POST /installments/{installment}/pay
     * Officer & Head — mark installment as paid immediately.
     */
    public function markPaid(ContractInstallment $installment)
    {
        $user = Auth::user();

        // Officers may only act on their own contracts
        if ($user->hasRole('Officer') && $installment->contract?->created_by !== $user->id) {
            abort(403);
        }

        if ($installment->paid_status === ContractInstallment::STATUS_PAID) {
            return back()->with('error', 'Installment is already marked as paid.');
        }

        DB::transaction(function () use ($installment, $user) {
            $oldStatus = $installment->paid_status;

            $installment->update([
                'paid_status' => ContractInstallment::STATUS_PAID,
                'paid_at'     => now(),
            ]);

            AuditService::log(
                'installment_marked_paid',
                $installment,
                ['paid_status' => $oldStatus],
                ['paid_status' => ContractInstallment::STATUS_PAID, 'paid_at' => now()->toDateTimeString()]
            );

            // Notify creator + all Heads
            $heads   = User::role('Head')->get();
            $creator = $installment->contract?->creator;
            $recipients = $heads
                ->when($creator && !$heads->contains('id', $creator->id), fn ($c) => $c->push($creator))
                ->unique('id');

            Notification::send($recipients, new InstallmentAlert($installment, InstallmentAlert::TYPE_PAID));
        });

        return back()->with('success', 'Installment marked as paid.');
    }
}
