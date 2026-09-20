<?php

namespace App\Enums;

enum SubmissionStatus: string
{
    case PENDING = 'Pending';
    case REVISION = 'Revisi';
    case REJECTED = 'Ditolak';
    case APPROVED = 'ACC';
    case NOT_APPLICABLE = 'N/A';
}
