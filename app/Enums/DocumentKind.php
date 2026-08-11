<?php

namespace App\Enums;

enum DocumentKind: string
{
    case Submission = 'submission';
    case Supplement = 'supplement';
    case Result = 'result';
}
