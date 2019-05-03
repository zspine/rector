<?php

namespace Rector\ZendToSymfony\Tests\Rector\Class_\ZendControllerToSymfonyControllerRector\Fixture;

class FooController extends Zend_Controller_Action
{
    public function barAction()
    {
    }
}

?>
-----
<?php

namespace Rector\ZendToSymfony\Tests\Rector\Class_\ZendControllerToSymfonyControllerRector\Fixture;

class FooController \extends Symfony\Bundle\FrameworkBundle\Controller
{
    public function barAction()
    {
    }
}

?>
