<?php

namespace App\Enums;

enum ApplicationStatus: string
{
    case Received = 'received';
    case Processing = 'processing';
    case SupplementRequired = 'supplement_required';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
