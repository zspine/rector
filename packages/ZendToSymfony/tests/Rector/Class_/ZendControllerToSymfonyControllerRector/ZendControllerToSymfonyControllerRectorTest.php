<?php declare(strict_types=1);

namespace Rector\ZendToSymfony\Tests\Rector\Class_\ZendControllerToSymfonyControllerRector;

use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ZendControllerToSymfonyControllerRectorTest extends AbstractRectorTestCase
{
    public function test(): void
    {
        $this->doTestFiles([
            __DIR__ . '/Fixture/fixture.php.inc'
        ]);
    }

    protected function getRectorClass(): string
    {
        return \Rector\ZendToSymfony\Rector\Class_\ZendControllerToSymfonyControllerRector::class;
    }
}
