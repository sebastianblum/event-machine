<?php
/**
 * This file is part of the proophsoftware/event-machine.
 * (c) 2017-2018 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

use Prooph\EventMachine\Persistence\InMemoryConnection;
use Prooph\EventMachine\Persistence\InMemoryEventStore;
use Prooph\EventMachine\Projecting\InMemory\InMemoryProjectionManager;
use Prooph\EventStore\Projection\Projector;
use Prooph\EventStore\Stream;
use Prooph\EventStore\StreamName;

require __DIR__ . '/../../../vendor/autoload.php';

$connection = new InMemoryConnection();
$eventStore = new InMemoryEventStore($connection);
$eventStore->create(new Stream(new StreamName('user-123'), new ArrayIterator([])));

$projectionManager = new InMemoryProjectionManager($eventStore, $connection);
$projection = $projectionManager->createProjection(
    'test_projection',
    [
        Projector::OPTION_PCNTL_DISPATCH => true,
    ]
);
\pcntl_signal(SIGQUIT, function () use ($projection) {
    $projection->stop();
    exit(SIGUSR1);
});
$projection
    ->fromStream('user-123')
    ->whenAny(function () {
    })
    ->run();
