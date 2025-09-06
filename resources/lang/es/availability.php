<?php

/**
 * Availability Module Localization - Spanish
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
    'created'                => 'Regla de disponibilidad creada exitosamente.',
    'updated'                => 'Disponibilidad actualizada.',
    'updated_successfully'   => 'Disponibilidad actualizada.',
    'deleted'                => 'Regla de disponibilidad eliminada exitosamente.',
    'fetched'                => 'Disponibilidad obtenida exitosamente.',

    /*
    |--------------------------------------------------------------------------
    | Status Update Messages
    |--------------------------------------------------------------------------
    */
    'status_updated'         => 'Estado actualizado exitosamente.',
    'status_online'          => 'Ahora estás en línea y disponible.',
    'status_offline'         => 'Ahora estás desconectado.',
    'status_busy'            => 'Estado cambiado a ocupado.',
    'status_away'            => 'Estado cambiado a ausente.',

    /*
    |--------------------------------------------------------------------------
    | Public Availability Access
    |--------------------------------------------------------------------------
    */
    'public_retrieved'       => 'Disponibilidad pública obtenida exitosamente.',
    'public_not_found'       => 'No se encontró disponibilidad pública para esta identidad.',
    'public_hidden'          => 'La disponibilidad de este creador está configurada como privada.',

    /*
    |--------------------------------------------------------------------------
    | Validation Error Messages
    |--------------------------------------------------------------------------
    */
    'conflict'               => 'Ya existe una regla de disponibilidad para el horario especificado.',
    'overlap'                => 'Esta regla de disponibilidad se superpone con un horario existente.',
    'invalid_time_range'     => 'La hora de fin debe ser posterior a la hora de inicio.',
    'invalid_day_of_week'    => 'El día de la semana debe estar entre 0 (domingo) y 6 (sábado).',
    'invalid_status'         => 'El estado debe ser uno de: en línea, desconectado, ocupado, ausente.',
    'no_active_identity'     => 'No se encontró identidad activa. Por favor activa una identidad primero.',

    /*
    |--------------------------------------------------------------------------
    | Booking Integration Messages
    |--------------------------------------------------------------------------
    */
    'booking_conflict'       => 'Este cambio de disponibilidad entra en conflicto con reservas existentes.',
    'booking_updated'        => 'Las solicitudes de reserva relacionadas han sido actualizadas.',
    'booking_cancelled'      => 'Las solicitudes de reserva en conflicto han sido canceladas.',

    /*
    |--------------------------------------------------------------------------
    | Booking Type Definitions
    |--------------------------------------------------------------------------
    */
    'booking_type' => [
        'chat'           => 'Chat',
        'call'           => 'Llamada de Voz',
        'video'          => 'Videollamada',
        'in_person'      => 'En Persona',
        'content_request'=> 'Solicitud de Contenido',
        'consultation'   => 'Consulta',
        'session'        => 'Sesión',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bulk Operation Messages
    |--------------------------------------------------------------------------
    */
    'bulk_updated'           => 'Múltiples reglas de disponibilidad actualizadas exitosamente.',
    'bulk_created'           => 'Horario de disponibilidad importado exitosamente.',
    'bulk_deleted'           => 'Reglas de disponibilidad seleccionadas eliminadas exitosamente.',
    'bulk_no_changes'        => 'No se realizaron cambios en tu disponibilidad.',

    /*
    |--------------------------------------------------------------------------
    | Broadcasting & Real-time Messages
    |--------------------------------------------------------------------------
    */
    'broadcast_update'       => 'Disponibilidad actualizada en tiempo real.',
    'broadcast_offline'      => 'El creador se desconectó.',
    'broadcast_online'       => 'El creador ahora está disponible.',
];
