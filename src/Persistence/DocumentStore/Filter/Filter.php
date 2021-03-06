<?php
/**
 * This file is part of the proophsoftware/event-machine.
 * (c) 2017-2018 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventMachine\Persistence\DocumentStore\Filter;

interface Filter
{
    public const NOT_SET_PROPERTY = '___EVENT_MACHINE_FILTER_NOT_SET_PROPERTY___';

    public function match(array $doc): bool;
}
