<?php

namespace Starlit\App;

use Symfony\Component\HttpFoundation\Request;

interface ViewAwareControllerInterface extends ControllerInterface
{
    public function setView(ViewInterface $view);
}
