<?php

declare(strict_types=1);
/**
 * This file is part of DTM-PHP.
 *
 * @license  https://github.com/dtm-php/dtm-sample/blob/master/LICENSE
 */
namespace App\Controller;

abstract class AbstractController
{

    protected string $serviceUri;

    public function __construct()
    {
        $this->serviceUri = env('DTM_SELF_CLIENT_URI');
    }

}
