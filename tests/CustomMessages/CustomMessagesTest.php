<?php
/**
 * This file is part of the proophsoftware/event-machine.
 * (c) 2017-2018 prooph software GmbH <contact@prooph.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventMachineTest\CustomMessages;

use Prooph\EventMachine\Container\EventMachineContainer;
use Prooph\EventMachine\EventMachine;
use Prooph\EventMachine\Persistence\Stream;
use Prooph\EventMachineTest\BasicTestCase;
use Prooph\EventMachineTest\CustomMessages\Stub\Aggregate\Todo;
use Prooph\EventMachineTest\CustomMessages\Stub\Descrption\TodoDescription;
use Prooph\EventMachineTest\CustomMessages\Stub\Event\TodoMarkedAsDone;
use Prooph\EventMachineTest\CustomMessages\Stub\Event\TodoPosted;
use Prooph\EventMachineTest\CustomMessages\Stub\Projection\TodoProjector;
use Prooph\EventMachineTest\CustomMessages\Stub\Query\GetDoneTodos;
use Prooph\EventMachineTest\CustomMessages\Stub\Query\GetTodo;
use Prooph\EventMachineTest\CustomMessages\Stub\Query\TodoFinder;
use Ramsey\Uuid\Uuid;

class CustomMessagesTest extends BasicTestCase
{
    /**
     * @test
     */
    public function it_passes_custom_messages_to_userland_code_if_registered()
    {
        $eventMachine = new EventMachine();

        $eventMachine->load(TodoDescription::class);

        $pmEvt = null;

        $eventMachine->on(TodoDescription::EVT_TODO_POSTED, function (TodoPosted $evt) use (&$pmEvt) {
            $pmEvt = $evt;
        });

        $eventMachine->watch(Stream::ofWriteModel())
            ->with('TodoProjection', TodoProjector::class);

        $todoFinder = new TodoFinder();
        $todoProjector = new TodoProjector();

        $eventMachine->initialize(new EventMachineContainer($eventMachine));

        $eventMachine->bootstrapInTestMode([], [
            TodoFinder::class => $todoFinder,
            TodoProjector::class => $todoProjector,
        ]);

        $todoId = Uuid::uuid4()->toString();

        $postTodo = $eventMachine->messageFactory()->createMessageFromArray(
            TodoDescription::CMD_POST_TODO,
            [
                'payload' => [
                    'todoId' => $todoId,
                    'text' => 'Test todo',
                ],
            ]
        );

        $eventMachine->dispatch($postTodo);

        $expectedTodo = [
            'todoId' => $todoId,
            'text' => 'Test todo',
        ];

        $recordedEvents = $eventMachine->popRecordedEventsOfTestSession();

        $this->assertCount(1, $recordedEvents);

        $this->assertEquals($expectedTodo, $recordedEvents[0]->payload());
        //Test that custom event metadata is passed along
        $this->assertEquals('test', $recordedEvents[0]->metadata()['meta']);

        $todo = $eventMachine->loadAggregateState(Todo::class, $todoId);

        $this->assertEquals([
            'todoId' => $todoId,
            'text' => 'Test todo',
        ], $todo);

        $this->assertInstanceOf(TodoPosted::class, $pmEvt);

        //Verify that projections receive custom events
        $eventMachine->runProjections(false);

        $this->assertInstanceOf(TodoPosted::class, $todoProjector->getLastHandledEvent());

        //Verify that finders receive custom queries
        $getTodo = $eventMachine->messageFactory()->createMessageFromArray(
            TodoDescription::QRY_GET_TODO,
            [
                'payload' => [
                    'todoId' => $todoId,
                ],
            ]
        );

        $eventMachine->dispatch($getTodo);

        $this->assertInstanceOf(GetTodo::class, $todoFinder->getLastReceivedQuery());
        $this->assertEquals($todoId, $todoFinder->getLastReceivedQuery()->todoId());
    }

    /**
     * @test
     */
    public function it_passes_prooph_messages_to_userland_code_if_registered()
    {
        $eventMachine = new EventMachine();

        $eventMachine->load(TodoDescription::class);

        $pmEvt = null;

        $eventMachine->on(TodoDescription::EVT_TODO_MAKRED_AS_DONE, function (TodoMarkedAsDone $evt) use (&$pmEvt) {
            $pmEvt = $evt;
        });

        $eventMachine->watch(Stream::ofWriteModel())
            ->with('TodoProjection', TodoProjector::class);

        $todoFinder = new TodoFinder();
        $todoProjector = new TodoProjector();

        $eventMachine->initialize(new EventMachineContainer($eventMachine));

        $todoId = Uuid::uuid4()->toString();

        $eventMachine->bootstrapInTestMode([
            $eventMachine->messageFactory()->createMessageFromArray(
                TodoDescription::EVT_TODO_POSTED,
                [
                    'payload' => [
                        'todoId' => $todoId,
                        'text' => 'Test todo',
                    ],
                ]
            ),
        ], [
            TodoFinder::class => $todoFinder,
            TodoProjector::class => $todoProjector,
        ]);

        $markAsDone = $eventMachine->messageFactory()->createMessageFromArray(
            TodoDescription::CMD_MARK_AS_DONE,
            [
                'payload' => [
                    'todoId' => $todoId,
                ],
            ]
        );

        $eventMachine->dispatch($markAsDone);

        $recordedEvents = $eventMachine->popRecordedEventsOfTestSession();

        $this->assertCount(1, $recordedEvents);

        $this->assertEquals(['todoId' => $todoId], $recordedEvents[0]->payload());
        //Test that custom event metadata is passed along
        $this->assertEquals('test', $recordedEvents[0]->metadata()['meta']);

        $todo = $eventMachine->loadAggregateState(Todo::class, $todoId);

        $this->assertEquals([
            'todoId' => $todoId,
            'text' => 'Test todo',
            'done' => true,
        ], $todo);

        $this->assertInstanceOf(TodoMarkedAsDone::class, $pmEvt);

        //Verify that projections receive custom events
        $eventMachine->runProjections(false);

        $this->assertInstanceOf(TodoMarkedAsDone::class, $todoProjector->getLastHandledEvent());

        //Verify that finders receive custom queries
        $getDoneTodos = $eventMachine->messageFactory()->createMessageFromArray(
            TodoDescription::QRY_GET_DONE_TODOS,
            [
                'payload' => [
                    'todoId' => $todoId,
                ],
            ]
        );

        $eventMachine->dispatch($getDoneTodos);

        $this->assertInstanceOf(GetDoneTodos::class, $todoFinder->getLastReceivedQuery());
        $this->assertEquals($todoId, $todoFinder->getLastReceivedQuery()->todoId());
    }
}