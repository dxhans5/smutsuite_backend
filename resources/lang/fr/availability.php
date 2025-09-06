<?php

/**
 * Availability Module Localization - French
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
    'created'                => 'Règle de disponibilité créée avec succès.',
    'updated'                => 'Disponibilité mise à jour.',
    'updated_successfully'   => 'Disponibilité mise à jour.',
    'deleted'                => 'Règle de disponibilité supprimée avec succès.',
    'fetched'                => 'Disponibilité récupérée avec succès.',

    /*
    |--------------------------------------------------------------------------
    | Status Update Messages
    |--------------------------------------------------------------------------
    */
    'status_updated'         => 'Statut mis à jour avec succès.',
    'status_online'          => 'Vous êtes maintenant en ligne et disponible.',
    'status_offline'         => 'Vous êtes maintenant hors ligne.',
    'status_busy'            => 'Statut défini sur occupé.',
    'status_away'            => 'Statut défini sur absent.',

    /*
    |--------------------------------------------------------------------------
    | Public Availability Access
    |--------------------------------------------------------------------------
    */
    'public_retrieved'       => 'Disponibilité publique récupérée avec succès.',
    'public_not_found'       => 'Aucune disponibilité publique trouvée pour cette identité.',
    'public_hidden'          => 'La disponibilité de ce créateur est définie comme privée.',

    /*
    |--------------------------------------------------------------------------
    | Validation Error Messages
    |--------------------------------------------------------------------------
    */
    'conflict'               => 'Une règle de disponibilité existe déjà pour le créneau horaire donné.',
    'overlap'                => 'Cette règle de disponibilité chevauche avec un horaire existant.',
    'invalid_time_range'     => 'L\'heure de fin doit être postérieure à l\'heure de début.',
    'invalid_day_of_week'    => 'Le jour de la semaine doit être entre 0 (dimanche) et 6 (samedi).',
    'invalid_status'         => 'Le statut doit être l\'un des suivants : en ligne, hors ligne, occupé, absent.',
    'no_active_identity'     => 'Aucune identité active trouvée. Veuillez d\'abord activer une identité.',

    /*
    |--------------------------------------------------------------------------
    | Booking Integration Messages
    |--------------------------------------------------------------------------
    */
    'booking_conflict'       => 'Ce changement de disponibilité entre en conflit avec les réservations existantes.',
    'booking_updated'        => 'Les demandes de réservation associées ont été mises à jour.',
    'booking_cancelled'      => 'Les demandes de réservation en conflit ont été annulées.',

    /*
    |--------------------------------------------------------------------------
    | Booking Type Definitions
    |--------------------------------------------------------------------------
    */
    'booking_type' => [
        'chat'           => 'Chat',
        'call'           => 'Appel Vocal',
        'video'          => 'Appel Vidéo',
        'in_person'      => 'En Personne',
        'content_request'=> 'Demande de Contenu',
        'consultation'   => 'Consultation',
        'session'        => 'Session',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bulk Operation Messages
    |--------------------------------------------------------------------------
    */
    'bulk_updated'           => 'Plusieurs règles de disponibilité mises à jour avec succès.',
    'bulk_created'           => 'Planning de disponibilité importé avec succès.',
    'bulk_deleted'           => 'Règles de disponibilité sélectionnées supprimées avec succès.',
    'bulk_no_changes'        => 'Aucun changement apporté à votre disponibilité.',

    /*
    |--------------------------------------------------------------------------
    | Broadcasting & Real-time Messages
    |--------------------------------------------------------------------------
    */
    'broadcast_update'       => 'Disponibilité mise à jour en temps réel.',
    'broadcast_offline'      => 'Le créateur est passé hors ligne.',
    'broadcast_online'       => 'Le créateur est maintenant disponible.',
];
