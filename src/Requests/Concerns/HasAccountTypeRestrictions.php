<?php

namespace InterWorks\PowerBI\Requests\Concerns;

use InterWorks\PowerBI\Enums\ConnectionAccountType;

/**
 * Trait to enforce account type restrictions on Power BI API endpoints.
 *
 * No constructor modification needed - the PowerBIConnectorBase handles enforcement automatically.
 */
trait HasAccountTypeRestrictions
{
    /**
     * Define which account types are restricted from accessing this endpoint.
     *
     * @return array<ConnectionAccountType>
     */
    abstract public function restrictedAccountTypes(): array;
}
