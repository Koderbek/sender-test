<?php

declare(strict_types=1);

use App\Application\Actions\Event\SendEventsAction;
use Slim\App;

return function (App $app) {
    $app->post('/events', SendEventsAction::class);
};
