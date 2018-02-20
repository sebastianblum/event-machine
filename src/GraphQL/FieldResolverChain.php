<?php
/**
 * This file is part of the proophsoftware/event-machine.
 * (c) 2017-2018 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventMachine\GraphQL;

use GraphQL\Type\Definition\ResolveInfo;
use Psr\Http\Message\ServerRequestInterface;
use React\Promise\FulfilledPromise;
use React\Promise\PromiseInterface;

final class FieldResolverChain implements FieldResolver
{
    /**
     * @var FieldResolver[]
     */
    private $chain;

    public function __construct(FieldResolver ...$fieldResolvers)
    {
        $this->chain = $fieldResolvers;
    }

    public function canResolve($source, array $args, ServerRequestInterface $context, ResolveInfo $info): bool
    {
        foreach ($this->chain as $resolver) {
            if ($resolver->canResolve($source, $args, $context, $info)) {
                return true;
            }
        }

        return false;
    }

    public function resolve($source, array $args, ServerRequestInterface $context, ResolveInfo $info): PromiseInterface
    {
        foreach ($this->chain as $resolver) {
            if ($resolver->canResolve($source, $args, $context, $info)) {
                return $resolver->resolve($source, $args, $context, $info);
            }
        }

        return new FulfilledPromise();
    }
}