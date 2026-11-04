<?php

use InterWorks\PowerBI\Connectors\PowerBIServicePrincipal;
use InterWorks\PowerBI\Enums\ConnectionAccountType;
use Saloon\Enums\Method;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;
use Saloon\Http\Request;

test('only requests using the trait have account type restrictions enforced', function () {
    $request = new class extends Request
    {
        protected Method $method = Method::GET;

        public function resolveEndpoint(): string
        {
            return '/no-trait';
        }

        /**
         * @return array<ConnectionAccountType>
         */
        public function restrictedAccountTypes(): array
        {
            return [ConnectionAccountType::ServicePrincipal];
        }
    };

    $connector = new PowerBIServicePrincipal('tenant', 'client', 'secret');
    $response = $connector->send($request, new MockClient([MockResponse::make(['ok' => true])]));

    expect($response->status())->toBe(200);
});
