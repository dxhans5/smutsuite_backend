<?php

namespace App\Enums;

enum BookingType: string
{
    case CONSULTATION = 'consultation';
    case CONTENT_CREATION = 'content_creation';
    case VIRTUAL_SESSION = 'virtual_session';
    case IN_PERSON = 'in_person';
    case CUSTOM = 'custom';
}
