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
        $this->db = System::getContainer()->get('doctrine.dbal.default_connection');
    }

    public function __invoke(Request $request): Response
    {
        $itemId = $request->query->get('id');
        $class = $request->query->get('class');
        $oldClass = $request->query->get('oldclass');
        $grid_columns = $this->db->fetchOne("SELECT grid_columns FROM tl_content WHERE id = ?", [$itemId] );

        $gridArr = StringUtil::deserialize($grid_columns);

        $gridArr = array_replace($gridArr,
            array_fill_keys(
                array_keys($gridArr, $oldClass),
                $class
            )
        );

        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder
            ->update('tl_content')
            ->set('grid_columns', '?')
            ->where('id = ?')
            ->setParameter(0, serialize($gridArr))
            ->setParameter(0, $itemId)
        ;
        //$objResult = \Database::getInstance()->prepare("UPDATE tl_content SET grid_columns=? WHERE id=?")->execute($classes, $itemId);

        //return new Response(StringUtil::deserialize($objResult));

        return new Response(serialize($gridArr));
    }
}
