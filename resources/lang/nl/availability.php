<?php

/**
 * Availability Module Localization - Dutch
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
    */
    'created'                => 'Beschikbaarheidsregel succesvol aangemaakt.',
    'updated'                => 'Beschikbaarheid bijgewerkt.',
    'updated_successfully'   => 'Beschikbaarheid bijgewerkt.',
    'deleted'                => 'Beschikbaarheidsregel succesvol verwijderd.',
    'fetched'                => 'Beschikbaarheid succesvol opgehaald.',

    /*
    |--------------------------------------------------------------------------
    | Status Update Messages
    |--------------------------------------------------------------------------
    */
    'status_updated'         => 'Status succesvol bijgewerkt.',
    'status_online'          => 'Je bent nu online en beschikbaar.',
    'status_offline'         => 'Je bent nu offline.',
    'status_busy'            => 'Status ingesteld op bezet.',
    'status_away'            => 'Status ingesteld op afwezig.',

    /*
    |--------------------------------------------------------------------------
    | Public Availability Access
    |--------------------------------------------------------------------------
    */
    'public_retrieved'       => 'Openbare beschikbaarheid succesvol opgehaald.',
    'public_not_found'       => 'Geen openbare beschikbaarheid gevonden voor deze identiteit.',
    'public_hidden'          => 'De beschikbaarheid van deze creator is ingesteld op privé.',

    /*
    |--------------------------------------------------------------------------
    | Validation Error Messages
    |--------------------------------------------------------------------------
    */
    'conflict'               => 'Er bestaat al een beschikbaarheidsregel voor het opgegeven tijdslot.',
    'overlap'                => 'Deze beschikbaarheidsregel overlapt met een bestaand schema.',
    'invalid_time_range'     => 'Eindtijd moet na de starttijd zijn.',
    'invalid_day_of_week'    => 'Dag van de week moet tussen 0 (zondag) en 6 (zaterdag) zijn.',
    'invalid_status'         => 'Status moet een van de volgende zijn: online, offline, bezet, afwezig.',
    'no_active_identity'     => 'Geen actieve identiteit gevonden. Activeer eerst een identiteit.',

    /*
    |--------------------------------------------------------------------------
    | Booking Integration Messages
    |--------------------------------------------------------------------------
    */
    'booking_conflict'       => 'Deze beschikbaarheidswijziging conflicteert met bestaande boekingen.',
    'booking_updated'        => 'Gerelateerde boekingsverzoeken zijn bijgewerkt.',
    'booking_cancelled'      => 'Conflicterende boekingsverzoeken zijn geannuleerd.',

    /*
    |--------------------------------------------------------------------------
    | Booking Type Definitions
    |--------------------------------------------------------------------------
    */
    'booking_type' => [
        'chat'           => 'Chat',
        'call'           => 'Spraakoproep',
        'video'          => 'Video-oproep',
        'in_person'      => 'Persoonlijk',
        'content_request'=> 'Contentverzoek',
        'consultation'   => 'Consultatie',
        'session'        => 'Sessie',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bulk Operation Messages
    |--------------------------------------------------------------------------
    */
    'bulk_updated'           => 'Meerdere beschikbaarheidsregels succesvol bijgewerkt.',
    'bulk_created'           => 'Beschikbaarheidsschema succesvol geïmporteerd.',
    'bulk_deleted'           => 'Geselecteerde beschikbaarheidsregels succesvol verwijderd.',
    'bulk_no_changes'        => 'Geen wijzigingen aangebracht in je beschikbaarheid.',

    /*
    |--------------------------------------------------------------------------
    | Broadcasting & Real-time Messages
    |--------------------------------------------------------------------------
    */
    'broadcast_update'       => 'Beschikbaarheid bijgewerkt in realtime.',
    'broadcast_offline'      => 'Creator is offline gegaan.',
    'broadcast_online'       => 'Creator is nu beschikbaar.',
];
