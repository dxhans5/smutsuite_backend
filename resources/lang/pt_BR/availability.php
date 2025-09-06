<?php

/**
 * Availability Module Localization - Brazilian Portuguese
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
    'created'                => 'Regra de disponibilidade criada com sucesso.',
    'updated'                => 'Disponibilidade atualizada.',
    'updated_successfully'   => 'Disponibilidade atualizada.',
    'deleted'                => 'Regra de disponibilidade excluída com sucesso.',
    'fetched'                => 'Disponibilidade recuperada com sucesso.',

    /*
    |--------------------------------------------------------------------------
    | Status Update Messages
    |--------------------------------------------------------------------------
    */
    'status_updated'         => 'Status atualizado com sucesso.',
    'status_online'          => 'Você está agora online e disponível.',
    'status_offline'         => 'Você está agora offline.',
    'status_busy'            => 'Status definido para ocupado.',
    'status_away'            => 'Status definido para ausente.',

    /*
    |--------------------------------------------------------------------------
    | Public Availability Access
    |--------------------------------------------------------------------------
    */
    'public_retrieved'       => 'Disponibilidade pública recuperada com sucesso.',
    'public_not_found'       => 'Nenhuma disponibilidade pública encontrada para esta identidade.',
    'public_hidden'          => 'A disponibilidade deste criador está definida como privada.',

    /*
    |--------------------------------------------------------------------------
    | Validation Error Messages
    |--------------------------------------------------------------------------
    */
    'conflict'               => 'Uma regra de disponibilidade já existe para o horário especificado.',
    'overlap'                => 'Esta regra de disponibilidade se sobrepõe a um cronograma existente.',
    'invalid_time_range'     => 'Horário de término deve ser posterior ao horário de início.',
    'invalid_day_of_week'    => 'Dia da semana deve estar entre 0 (domingo) e 6 (sábado).',
    'invalid_status'         => 'Status deve ser um dos seguintes: online, offline, ocupado, ausente.',
    'no_active_identity'     => 'Nenhuma identidade ativa encontrada. Por favor, ative uma identidade primeiro.',

    /*
    |--------------------------------------------------------------------------
    | Booking Integration Messages
    |--------------------------------------------------------------------------
    */
    'booking_conflict'       => 'Esta mudança de disponibilidade conflita com reservas existentes.',
    'booking_updated'        => 'Solicitações de reserva relacionadas foram atualizadas.',
    'booking_cancelled'      => 'Solicitações de reserva conflitantes foram canceladas.',

    /*
    |--------------------------------------------------------------------------
    | Booking Type Definitions
    |--------------------------------------------------------------------------
    */
    'booking_type' => [
        'chat'           => 'Chat',
        'call'           => 'Chamada de Voz',
        'video'          => 'Videochamada',
        'in_person'      => 'Presencial',
        'content_request'=> 'Solicitação de Conteúdo',
        'consultation'   => 'Consulta',
        'session'        => 'Sessão',
    ],

    /*
    |--------------------------------------------------------------------------
    | Bulk Operation Messages
    |--------------------------------------------------------------------------
    */
    'bulk_updated'           => 'Múltiplas regras de disponibilidade atualizadas com sucesso.',
    'bulk_created'           => 'Cronograma de disponibilidade importado com sucesso.',
    'bulk_deleted'           => 'Regras de disponibilidade selecionadas excluídas com sucesso.',
    'bulk_no_changes'        => 'Nenhuma alteração feita na sua disponibilidade.',

    /*
    |--------------------------------------------------------------------------
    | Broadcasting & Real-time Messages
    |--------------------------------------------------------------------------
    */
    'broadcast_update'       => 'Disponibilidade atualizada em tempo real.',
    'broadcast_offline'      => 'Criador ficou offline.',
    'broadcast_online'       => 'Criador está agora disponível.',
];
