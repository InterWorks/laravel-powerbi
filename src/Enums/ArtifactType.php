<?php

namespace InterWorks\PowerBI\Enums;

enum ArtifactType: string
{
    case Report = 'Report';
    case PaginatedReport = 'PaginatedReport';
    case Dashboard = 'Dashboard';
    case Dataset = 'Dataset';
    case Dataflow = 'Dataflow';
    case PersonalGroup = 'PersonalGroup';
    case Group = 'Group';
    case Workspace = 'Workspace';
    case Capacity = 'Capacity';
    case App = 'App';

    public static function fromString(string $value): self
    {
        return match ($value) {
            'Report' => self::Report,
            'PaginatedReport' => self::PaginatedReport,
            'Dashboard' => self::Dashboard,
            'Dataset' => self::Dataset,
            'Dataflow' => self::Dataflow,
            'PersonalGroup' => self::PersonalGroup,
            'Group' => self::Group,
            'Workspace' => self::Workspace,
            'Capacity' => self::Capacity,
            'App' => self::App,
            default => throw new \InvalidArgumentException("Invalid ArtifactType: $value"),
        };
    }
}
