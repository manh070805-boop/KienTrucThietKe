<?php

namespace App\Enums;

enum RoleCode: string
{
    case STUDENT = 'STUDENT';
    case COORDINATOR = 'COORDINATOR';
    case STAFF = 'STAFF';
    case ADMIN = 'ADMIN';
}
