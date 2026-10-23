<?php

namespace App\Enums;

enum MessageKind: string { case COMMENT = 'COMMENT'; case INFO_REQUEST = 'INFO_REQUEST'; case INFO_RESPONSE = 'INFO_RESPONSE'; case RESOLUTION = 'RESOLUTION'; }
