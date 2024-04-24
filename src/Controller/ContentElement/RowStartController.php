<?php

declare(strict_types=1);

namespace ManiaxAtWork\ContaoGridBundle\Controller\ContentElement;

use Contao\BackendTemplate;
use Contao\ContentModel;
use Contao\CoreBundle\Controller\ContentElement\AbstractContentElementController;
use Contao\CoreBundle\ServiceAnnotation\ContentElement;
use Contao\CoreBundle\Twig\FragmentTemplate;
use Symfony\Component\HttpFoundation\Request;
use ManiaxAtWork\ContaoGridBundle\GridClasses;
use Symfony\Component\HttpFoundation\Response;

/**
 * @ContentElement("rowStart", category="maw_grid")
 */
class RowStartController extends AbstractContentElementController
{
    public function __construct(private readonly GridClasses $gridClasses)
    {
    }

    protected function getResponse(FragmentTemplate $template, ContentModel $model, Request $request): Response
    {
        $rowClass = $this->gridClasses->getRowClass();
        $template->rowClass = $rowClass;

        if ($this->container->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $template = new BackendTemplate('be_wildcard');

            return $template->getResponse();
        }

        return $template->getResponse();
    }
}
