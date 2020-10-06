<?php

namespace Vendor\ModuleName\Controller;

use Bitrix\Main\Engine\Controller;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Connection extends Controller
{
    public function configureActions()
    {
        return [
            'some' => [
                'prefilters' => []
            ],
        ];
    }

    public function someAction()
    {

        return array('success' => true);
    }
}