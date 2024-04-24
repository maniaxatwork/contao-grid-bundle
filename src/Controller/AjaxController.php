<?php

declare(strict_types=1);

/*
 * This file is part of maniaxatwork/contao-grid-bundle.
 *
 * (c) maniax-at-work.de <https://www.maniax-at-work.de>
 *
 * @license MIT
 */

namespace ManiaxAtWork\ContaoGridBundle\Controller;

use Contao\StringUtil;
use Contao\System;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

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
        $grid_columns = $this->db->fetchOne('SELECT grid_columns FROM tl_content WHERE id = ?', [$itemId]);

        $gridArr = StringUtil::deserialize($grid_columns);

        if (\in_array($oldClass, $gridArr, true)) {
            $gridArr = array_replace($gridArr,
                array_fill_keys(
                    array_keys($gridArr, $oldClass, true),
                    $class,
                ),
            );
        } else {
            $gridArr[] = $class;
        }

        $queryBuilder = $this->db->createQueryBuilder();
        $queryBuilder
            ->update('tl_content')
            ->set('grid_columns', ':gridColumns')
            ->where('id = :itemId')
            ->setParameter('gridColumns', serialize($gridArr))
            ->setParameter('itemId', $itemId)
            ->execute()
        ;

        return new Response(serialize($gridArr));
    }
}
