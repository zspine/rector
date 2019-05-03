<?php declare(strict_types=1);

namespace Rector\ZendToSymfony\Rector\Class_;

use PhpParser\Node;
use Rector\Rector\AbstractRector;
use Rector\RectorDefinition\CodeSample;
use Rector\RectorDefinition\RectorDefinition;
/**
 * @see https://framework.zend.com/manual/1.7/en/zend.controller.action.html
 */
final class ZendControllerToSymfonyControllerRector extends AbstractRector
{
    public function getDefinition(): RectorDefinition
    {
        return new RectorDefinition('Refactors Zend Controller to Symfony one', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class FooController extends Zend_Controller_Action
{
    public function barAction()
    {
    }
}
CODE_SAMPLE
,
                <<<'CODE_SAMPLE'
class FooController \extends Symfony\Bundle\FrameworkBundle\Controller
{
    public function barAction()
    {
    }
}
CODE_SAMPLE

            )
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [\PhpParser\Node\Stmt\Class_::class];
    }

    /**
     * @param \PhpParser\Node\Stmt\Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        // change the node

        return $node;
    }
}
