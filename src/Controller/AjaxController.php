<?php

declare(strict_types=1);

namespace ManiaxAtWork\ContaoGridBundle\Controller;

use Contao\System;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;

#[Route('/ajaxcolsave', name: AjaxController::class, defaults: ['_scope' => 'backend', '_token_check' => true])]
#[AsController]
class AjaxController
{
    private $security;
    private $db;

    public function __construct(Security $security)
    {
        $this->security = $security;
        $this->db = System::getContainer()->get('doctrine.dbal.foo_connection');
    }

    public function __invoke(Request $request): Response
    {
       $itemId = $request->query->get('id');
       $classes = $request->query->get('class');
        $oldClass = $request->query->get('oldclass');
       $objResult = $this->db->prepare("SELECT grid_columns FROM tl_content WHERE id=?")->execute($itemId);
       //$objResult = \Database::getInstance()->prepare("UPDATE tl_content SET grid_columns=? WHERE id=?")->execute($classes, $itemId);

       //return new Response(StringUtil::deserialize($objResult));

       return new Response(StringUtil::deserialize($objResult));
    }
}
