<?php

/**
 * @package    Grav\Common\Processors
 *
 * @copyright  Copyright (C) 2015 - 2019 Trilby Media, LLC. All rights reserved.
 * @license    MIT License; see LICENSE file for details.
 */

namespace Grav\Common\Processors;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class PluginsProcessor extends ProcessorBase
{
    /** @var string */
    public $id = 'plugins';
    /** @var string */
    public $title = 'Plugins';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $this->startTimer();
        $this->container['accounts'];
        $this->container['pages'];
        $this->container['plugins']->init();
        $this->container->fireEvent('onPluginsInitialized');
        $this->stopTimer();

        return $handler->handle($request);
    }
}
