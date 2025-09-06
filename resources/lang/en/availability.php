<?php

/**
 * Availability Module Localization
 *
 * Contains all user-facing messages for the availability and scheduling system.
 * Covers CRUD operations, status updates, validation errors, and booking types.
 *
 * Used by:
 * - AvailabilityController (all methods)
 * - AvailabilityResource (API responses)
 * - AvailabilityUpdated event (broadcasting)
 * - Validation rules and form requests
 *
 * @package SmutSuite\Localization
 * @version 1.0
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Availability CRUD Operations
    |--------------------------------------------------------------------------
    |
    | Success messages for availability rule creation, updates, and deletion.
    | Used in controller responses and API resource envelopes.
    |
    */
    'created'                => 'Availability rule created successfully.',
    'updated'                => 'Availability updated.',
    'updated_successfully'   => 'Availability updated.',  // Test compatibility alias
    'deleted'                => 'Availability rule deleted successfully.',
    'fetched'                => 'Availability retrieved successfully.',

    /*
    |--------------------------------------------------------------------------
    | Status Update Messages
    |--------------------------------------------------------------------------
    |
    | Messages for real-time status changes (online, offline, busy, away).
    | Broadcast via WebSocket for immediate UI updates.
    |
    */
    'status_updated'         => 'Status updated successfully.',
    'status_online'          => 'You are now online and available.',
    'status_offline'         => 'You are now offline.',
    'status_busy'            => 'Status set to busy.',
    'status_away'            => 'Status set to away.',

    /*
    |--------------------------------------------------------------------------
    | Public Availability Access
    |--------------------------------------------------------------------------
    |
    | Messages for public-facing availability endpoints used in discovery
    | and booking flows. Respects identity privacy settings.
    |
    */
    'public_retrieved'       => 'Public availability retrieved successfully.',
    'public_not_found'       => 'No public availability found for this identity.',
    'public_hidden'          => 'This creator\'s availability is set to private.',

    /*
    |--------------------------------------------------------------------------
    | Validation Error Messages
    |--------------------------------------------------------------------------
    |
    | Error messages for availability rule validation failures.
    | Used in form requests and direct controller validation.
    |
    */
    'conflict'               => 'An availability rule already exists for the given time slot.',
    'overlap'                => 'This availability rule overlaps with an existing schedule.',
    'invalid_time_range'     => 'End time must be after start time.',
    'invalid_day_of_week'    => 'Day of week must be between 0 (Sunday) and 6 (Saturday).',
    'invalid_status'         => 'Status must be one of: online, offline, busy, away.',
    'no_active_identity'     => 'No active identity found. Please activate an identity first.',

    /*
    |--------------------------------------------------------------------------
    | Booking Integration Messages
    |--------------------------------------------------------------------------
    |
    | Messages related to availability and booking request interactions.
    | Used when availability changes affect pending bookings.
    |
    */
    'booking_conflict'       => 'This availability change conflicts with existing bookings.',
    'booking_updated'        => 'Related booking requests have been updated.',
    'booking_cancelled'      => 'Conflicting booking requests have been cancelled.',

    /*
    |--------------------------------------------------------------------------
    | Booking Type Definitions
    |--------------------------------------------------------------------------
    |
    | Human-readable labels for different types of availability/booking slots.
    | Used in UI dropdowns, API responses, and public profiles.
    |
    */
    'booking_type' => [
        'chat'           => 'Chat',
        'call'           => 'Voice Call',
        'video'          => 'Video Call',
        'in_person'      => 'In Person',
        'content_request'=> 'Content Request',
        'consultation'   => 'Consultation',
        'session'        => 'Session',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bulk Operation Messages
    |--------------------------------------------------------------------------
    |
    | Messages for bulk availability operations like importing schedules
    | or updating multiple rules simultaneously.
    |
    */
    'bulk_updated'           => 'Multiple availability rules updated successfully.',
    'bulk_created'           => 'Availability schedule imported successfully.',
    'bulk_deleted'           => 'Selected availability rules deleted successfully.',
    'bulk_no_changes'        => 'No changes were made to your availability.',

    /*
    |--------------------------------------------------------------------------
    | Broadcasting & Real-time Messages
    |--------------------------------------------------------------------------
    |
    | Messages sent via WebSocket events for real-time availability updates.
    | Seen by other users watching this creator's availability.
    |
    */
    'broadcast_update'       => 'Availability updated in real-time.',
    'broadcast_offline'      => 'Creator went offline.',
    'broadcast_online'       => 'Creator is now available.',
];
