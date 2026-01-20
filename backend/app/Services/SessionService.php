<?php

namespace App\Services;

use App\Models\Session;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SessionService
{
    /**
     * Create instances for a recurring session.
     *
     * @param Session $parentSession
     * @return void
     */
    public function createRecurringInstances(Session $parentSession): void
    {
        if (!$parentSession->isRecurring() || !$parentSession->recurrence_end_date) {
            return;
        }

        $currentDate = $parentSession->start_time->copy();
        $endDate = $parentSession->recurrence_end_date;
        $instances = [];

        // Move to next occurrence
        $currentDate = $this->getNextOccurrence($currentDate, $parentSession->recurrence_type);

        while ($currentDate->lte($endDate)) {
            // Calculate end time for this instance (preserving duration)
            $durationMinutes = $parentSession->end_time->diffInMinutes($parentSession->start_time);
            $instanceEndTime = $currentDate->copy()->addMinutes($durationMinutes);

            // Create instance data
            $instances[] = [
                'organization_id' => $parentSession->organization_id,
                'title' => $parentSession->title . ' (' . $currentDate->format('M d') . ')',
                'description' => $parentSession->description,
                'start_time' => $currentDate->toDateTimeString(),
                'end_time' => $instanceEndTime->toDateTimeString(),
                'location_lat' => $parentSession->location_lat,
                'location_lng' => $parentSession->location_lng,
                'location_name' => $parentSession->location_name,
                'radius_meters' => $parentSession->radius_meters,
                'session_type' => $parentSession->session_type,
                'status' => 'scheduled', // Future instances are scheduled
                'requires_payment' => $parentSession->requires_payment,
                'payment_amount' => $parentSession->payment_amount,
                'max_attendees' => $parentSession->max_attendees,
                'recurrence_type' => 'one_time', // Instances are one-time
                'recurrence_end_date' => null,
                'parent_session_id' => $parentSession->id,
                'capacity' => $parentSession->capacity,
                'current_count' => 0,
                'allow_entry_exit' => $parentSession->allow_entry_exit,
                'late_threshold_minutes' => $parentSession->late_threshold_minutes,
                'created_by' => $parentSession->created_by,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            // Move to next occurrence
            $currentDate = $this->getNextOccurrence($currentDate, $parentSession->recurrence_type);
        }

        if (!empty($instances)) {
            Session::insert($instances);
        }
    }

    /**
     * Get the next occurrence date based on recurrence type.
     *
     * @param Carbon $date
     * @param string $type
     * @return Carbon
     */
    private function getNextOccurrence(Carbon $date, string $type): Carbon
    {
        return match ($type) {
            'daily' => $date->addDay(),
            'weekly' => $date->addWeek(),
            'monthly' => $date->addMonth(),
            default => $date->addDay(), // Default fallback
        };
    }

    /**
     * Check if a user matches the session capacity requirements.
     *
     * @param Session $session
     * @return bool
     */
    public function canRegister(Session $session): bool
    {
        if (!$session->capacity) {
            return true;
        }

        return $session->current_count < $session->capacity;
    }

    /**
     * Update session status based on current time.
     * This could be called by a scheduled job.
     *
     * @return void
     */
    public function updateSessionStatuses(): void
    {
        $now = now();

        // Activate scheduled sessions that have reached start time
        Session::where('status', 'scheduled')
            ->where('start_time', '<=', $now)
            ->where('end_time', '>', $now)
            ->update(['status' => 'active']);

        // Complete active sessions that have passed end time
        Session::where('status', 'active')
            ->where('end_time', '<', $now)
            ->update(['status' => 'completed']);
    }
}
