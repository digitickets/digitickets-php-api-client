# DigiTickets PHP API Client

Provides access to the DigiTickets API.

## Usage
```
<?php

require __DIR__.'/vendor/autoload.php';

use DigiTicketsApiClient\ApiClient;

$apiClient = new ApiClient();
$apiClient->setApiKey('your_key_here');

$response = $apiClient->get('branches'); // Returns a PSR ResponseInterface.

// You can get an array of data from the response object with this method:
$branches = $apiClient->parseResponse($response);

print_r($branches);
// Returns:
// Array
// (
//     [0] => Array
//         (
//             [branchID] => 11
//             [name] => DigiTickets Demo Branch
```


## Testing

To run tests:

    phpunit tests

Some of these tests require an API Key to access the API. You can also specify a different API to make requests to 
when testing. Both of these go into a `.env` file in the root of this repository. Make a copy of `.env.example` to see
what this file should contain.

    cp .env.example .env

*Note:* Don't use the API key of a live company (or a test company you care about) for these tests, as the tests will create and delete data.
