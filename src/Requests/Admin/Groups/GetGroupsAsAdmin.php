<?php

namespace InterWorks\PowerBI\Requests\Admin\Groups;

use InterWorks\PowerBI\DTO\Groups;
use InvalidArgumentException;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;

class GetGroupsAsAdmin extends Request
{
    /**
     * The HTTP method of the request
     */
    protected Method $method = Method::GET;

    /**
     * @param  int  $top  The number of groups to return.
     * @param  array<string>  $expand  An array of data types, which will be expanded inline in the response.
     * @param  string|null  $filter  An OData filter expression that filters groups returned in the response.
     * @param  int|null  $skip  The number of groups to skip in the result set.
     */
    public function __construct(
        protected readonly int $top = 1000,
        /** @var array<string> */
        protected readonly array $expand = [],
        protected readonly ?string $filter = null,
        protected readonly ?int $skip = null,
    ) {
        if ($this->top > 5_000 || $this->top < 1) {
            // cf. https://learn.microsoft.com/en-us/rest/api/power-bi/admin/groups-get-groups-as-admin
            throw new InvalidArgumentException('The $top parameter must be between 1 and 5000.');
        }
    }

    /**
     * The endpoint for the request
     */
    public function resolveEndpoint(): string
    {
        return '/admin/groups';
    }

    /**
     * The default query parameters for the request
     */
    protected function defaultQuery(): array
    {
        // Top is required
        $parameters = [
            '$top' => $this->top,
        ];

        if ($this->getExpand() !== null) {
            $parameters['$expand'] = $this->getExpand();
        }

        if ($this->filter !== null) {
            $parameters['$filter'] = $this->filter;
        }

        if ($this->skip !== null) {
            $parameters['$skip'] = $this->skip;
        }

        return $parameters;
    }

    public function createDtoFromResponse(Response $response): mixed
    {
        $data = $response->json();

        // @phpstan-ignore argument.type
        $groups = Groups::fromArray($data['value']);

        return $groups;
    }

    //
    // Query parameter helpers
    //

    /**
     * Get the expand query parameter
     */
    public function getExpand(): ?string
    {
        // If the expand array is empty, return null
        if (empty($this->expand)) {
            return null;
        }

        // Validate the expand options
        $validOptions = [
            'users',
            'reports',
            'dashboards',
            'datasets',
            'dataflows',
            'workbooks',
        ];
        foreach ($this->expand as $option) {
            if (! in_array($option, $validOptions, true)) {
                throw new InvalidArgumentException("Invalid expand option: {$option}");
            }
        }

        return implode(',', $this->expand);
    }
}
