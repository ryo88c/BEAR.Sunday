<?php
namespace demoworld\Page\Cache;

use BEAR\Resource\Object as ResourceObject,
    BEAR\Resource\AbstractObject as AbstractPage,
    BEAR\Framework\Link\View\Php as PhpView;

use demoworld\Annotation\Cache,
    demoworld\Annotation\CacheUpdate;

/**
 * Cache page
 *
 * @Aspect
 */
class Page extends AbstractPage
{
    use PhpView;

    public function __construct()
    {
    }

    /**
     * @return ResourceObject
     *
     * @Cache
     */
    public function onGet($name)
    {
        $this['greeting'] = "Hello {$name} " . time();
        return $this;
    }

    /**
     * CacheUpdate("name")
     */
    public function onUpdate($name)
    {
    }
}