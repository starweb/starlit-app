<?php

namespace Starlit\App;

use Symfony\Component\HttpFoundation\Request;

interface ControllerInterface
{
    public function init();

    public function setApp(BaseApp $app);

    public function setRequest(Request $request);
}
