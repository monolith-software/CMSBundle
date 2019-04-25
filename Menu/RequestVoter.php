<?php

//declare(strict_types=1); // @todo Type error: strlen() expects parameter 1 to be string, null given (line 54)

namespace Monolith\Bundle\CMSBundle\Menu;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class RequestVoter implements VoterInterface
{
    /** @var string */
    protected $adminPath;

    /** @var RequestStack */
    protected $requestStack;

    /**
     * RequestVoter constructor.
     *
     * @param RequestStack $requestStack
     * @param string       $adminPath
     */
    public function __construct(RequestStack $requestStack, string $adminPath)
    {
        $this->adminPath    = $adminPath;
        $this->requestStack = $requestStack;
    }

    /**
     * @param ItemInterface $item
     *
     * @return bool
     */
    public function matchItem(ItemInterface $item)
    {
        $request = $this->requestStack->getCurrentRequest();

        $parent = $item->getParent();

        while (null !== $parent->getParent()) {
            $parent = $parent->getParent();
        }

        if ($item->getUri() === $request->getRequestUri() or
            $item->getUri() === $request->attributes->get('__current_folder_path', false)
        ) {
            // URL's completely match
            return true;
        } elseif (
            $item->getUri() !== $request->getBaseUrl().'/' and
            $item->getUri() !== $request->getBaseUrl().'/admin/' and
            $item->getUri() === substr($request->getRequestUri(), 0, strlen($item->getUri())) and
            $request->attributes->get('__selected_inheritance', true) and
            $parent->getExtra('select_intehitance', true)
        ) {
            // URL isn't just "/" and the first part of the URL match
            return true;
        }

        return false;
    }
}
