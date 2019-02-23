<?php

namespace Starlit\App;

use Symfony\Component\HttpFoundation\Request;

interface ControllerInterface
{
    public function setApp(BaseApp $app);

    public function setRequest(Request $request);

    public function init();

    public function dispatch();
}
