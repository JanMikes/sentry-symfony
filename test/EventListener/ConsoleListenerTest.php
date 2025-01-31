<?php

namespace Sentry\SentryBundle\Test\EventListener;

use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Sentry\Event;
use Sentry\SentryBundle\EventListener\ConsoleListener;
use Sentry\State\Hub;
use Sentry\State\HubInterface;
use Sentry\State\Scope;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;

class ConsoleListenerTest extends TestCase
{
    private $currentHub;
    private $currentScope;

    protected function setUp()
    {
        parent::setUp();

        $this->currentScope = $scope = new Scope();
        $this->currentHub = $this->prophesize(HubInterface::class);
        $this->currentHub->configureScope(Argument::type('callable'))
            ->shouldBeCalled()
            ->will(function ($arguments) use ($scope): void {
                $callable = $arguments[0];

                $callable($scope);
            });

        Hub::setCurrent($this->currentHub->reveal());
    }

    public function testOnConsoleCommandAddsCommandName(): void
    {
        $command = $this->prophesize(Command::class);
        $command->getName()
            ->willReturn('sf:command:name');

        $event = $this->prophesize(ConsoleCommandEvent::class);
        $event->getCommand()
            ->willReturn($command->reveal());

        $listener = new ConsoleListener($this->currentHub->reveal());

        $listener->onConsoleCommand($event->reveal());

        $this->assertSame(['command' => 'sf:command:name'], $this->getTagsContext($this->currentScope));
    }

    public function testOnConsoleCommandAddsPlaceholderCommandName(): void
    {
        $event = $this->prophesize(ConsoleCommandEvent::class);
        $event->getCommand()
            ->willReturn(null);

        $listener = new ConsoleListener($this->currentHub->reveal());

        $listener->onConsoleCommand($event->reveal());

        $this->assertSame(['command' => 'N/A'], $this->getTagsContext($this->currentScope));
    }

    private function getTagsContext(Scope $scope): array
    {
        $event = new Event();
        $scope->applyToEvent($event, []);

        return $event->getTagsContext()->toArray();
    }
}
