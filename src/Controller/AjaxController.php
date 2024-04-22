<?php

declare(strict_types=1);

namespace ManiaxAtWork\ContaoGridBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Security\Core\Security;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;
use Contao\StringUtil;

#[Route('/ajaxcolsave', name: AjaxController::class, defaults: ['_scope' => 'backend', '_token_check' => true])]
#[AsController]
class AjaxController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    public function __invoke(Request $request): Response
    {
      //  $token = $this->security->getToken();

       // $itemId = $request->request->get('id');
       // $classes = $request->request->get('class');
        $oldClass = $request->request->get('oldclass');
       // $objResult = \Database::getInstance()->prepare("SELECT grid_columns FROM tl_content WHERE id=?")->execute($itemId);
       // $objResult = \Database::getInstance()->prepare("UPDATE tl_content SET grid_columns=? WHERE id=?")->execute($classes, $itemId);

       //return new Response(StringUtil::deserialize($objResult));

       return new Response($oldClass);
    }
}
