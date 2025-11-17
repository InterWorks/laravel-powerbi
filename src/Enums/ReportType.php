<?php

namespace InterWorks\PowerBI\Enums;

enum ReportType: string
{
    case PowerBIReport = 'PowerBIReport';
    case PaginatedReport = 'PaginatedReport';

    public static function fromString(string $value): self
    {
        return match ($value) {
            'PowerBIReport' => self::PowerBIReport,
            'PaginatedReport' => self::PaginatedReport,
            default => throw new \InvalidArgumentException("Invalid ReportType: $value"),
        };
    }
}
