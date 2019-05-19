<?php declare(strict_types=1);

namespace Rector\CodingStyle\Tests\Rector\Namespace_\ImportFullyQualifiedNamesRector;

use Rector\CodingStyle\Rector\Namespace_\ImportFullyQualifiedNamesRector;
use Rector\Testing\PHPUnit\AbstractRectorTestCase;

final class ImportFullyQualifiedNamesRectorNonNamespacedTest extends AbstractRectorTestCase
{
    public function test(): void
    {
        $this->markTestSkipped('Decouple services first');

        $this->doTestFiles([__DIR__ . '/Fixture/NonNamespaced/simple.php.inc']);
    }

    protected function getRectorClass(): string
    {
        return ImportFullyQualifiedNamesRector::class;
    }
}
