<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Parse\ParseClient;

class Controller extends BaseController
{
    public function __construct()
    {
        ParseClient::initialize(
            'U6CaTTyJ2AGXWLdF3bfl89eWYR2BbMWrEE73Ynsd',
            'sz7rz1fuCIo4wRjNlM2lVrfuInsHbCRjr270tK8E',
            'vfUXDTVhAxvjteuuNq2in1fYrG7KKtdSMvchj1Qg');
    }
}
