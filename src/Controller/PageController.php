<?php

namespace SITE\Controller;

/**
 * Detta är bara ett exempel. Om det är många routs bör det delas upp i fler Controllers
 * Exempel:
 * ProductController
 * PageController
 * osv ...
 */

class PageController extends AbstractController
{
    public function home()
    {
        echo 'Min startsida';
    }
}
