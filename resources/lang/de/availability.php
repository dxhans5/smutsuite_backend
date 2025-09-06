<?php

/**
 * Availability Module Localization - German
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
    'created'                => 'Verfügbarkeitsregel erfolgreich erstellt.',
    'updated'                => 'Verfügbarkeit aktualisiert.',
    'updated_successfully'   => 'Verfügbarkeit aktualisiert.',
    'deleted'                => 'Verfügbarkeitsregel erfolgreich gelöscht.',
    'fetched'                => 'Verfügbarkeit erfolgreich abgerufen.',

    /*
    |--------------------------------------------------------------------------
    | Status Update Messages
    |--------------------------------------------------------------------------
    */
    'status_updated'         => 'Status erfolgreich aktualisiert.',
    'status_online'          => 'Sie sind jetzt online und verfügbar.',
    'status_offline'         => 'Sie sind jetzt offline.',
    'status_busy'            => 'Status auf beschäftigt gesetzt.',
    'status_away'            => 'Status auf abwesend gesetzt.',

    /*
    |--------------------------------------------------------------------------
    | Public Availability Access
    |--------------------------------------------------------------------------
    */
    'public_retrieved'       => 'Öffentliche Verfügbarkeit erfolgreich abgerufen.',
    'public_not_found'       => 'Keine öffentliche Verfügbarkeit für diese Identität gefunden.',
    'public_hidden'          => 'Die Verfügbarkeit dieses Creators ist auf privat gesetzt.',

    /*
    |--------------------------------------------------------------------------
    | Validation Error Messages
    |--------------------------------------------------------------------------
    */
    'conflict'               => 'Eine Verfügbarkeitsregel existiert bereits für den angegebenen Zeitslot.',
    'overlap'                => 'Diese Verfügbarkeitsregel überschneidet sich mit einem bestehenden Termin.',
    'invalid_time_range'     => 'Endzeit muss nach der Startzeit liegen.',
    'invalid_day_of_week'    => 'Wochentag muss zwischen 0 (Sonntag) und 6 (Samstag) liegen.',
    'invalid_status'         => 'Status muss einer der folgenden sein: online, offline, beschäftigt, abwesend.',
    'no_active_identity'     => 'Keine aktive Identität gefunden. Bitte aktivieren Sie zuerst eine Identität.',

    /*
    |--------------------------------------------------------------------------
    | Booking Integration Messages
    |--------------------------------------------------------------------------
    */
    'booking_conflict'       => 'Diese Verfügbarkeitsänderung steht im Konflikt mit bestehenden Buchungen.',
    'booking_updated'        => 'Zugehörige Buchungsanfragen wurden aktualisiert.',
    'booking_cancelled'      => 'Konfliktbehaftete Buchungsanfragen wurden storniert.',

    /*
    |--------------------------------------------------------------------------
    | Booking Type Definitions
    |--------------------------------------------------------------------------
    */
    'booking_type' => [
        'chat'           => 'Chat',
        'call'           => 'Sprachanruf',
        'video'          => 'Videoanruf',
        'in_person'      => 'Persönlich',
        'content_request'=> 'Content-Anfrage',
        'consultation'   => 'Beratung',
        'session'        => 'Session',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bulk Operation Messages
    |--------------------------------------------------------------------------
    */
    'bulk_updated'           => 'Mehrere Verfügbarkeitsregeln erfolgreich aktualisiert.',
    'bulk_created'           => 'Verfügbarkeitsplan erfolgreich importiert.',
    'bulk_deleted'           => 'Ausgewählte Verfügbarkeitsregeln erfolgreich gelöscht.',
    'bulk_no_changes'        => 'Keine Änderungen an Ihrer Verfügbarkeit vorgenommen.',

    /*
    |--------------------------------------------------------------------------
    | Broadcasting & Real-time Messages
    |--------------------------------------------------------------------------
    */
    'broadcast_update'       => 'Verfügbarkeit in Echtzeit aktualisiert.',
    'broadcast_offline'      => 'Creator ist offline gegangen.',
    'broadcast_online'       => 'Creator ist jetzt verfügbar.',
];
