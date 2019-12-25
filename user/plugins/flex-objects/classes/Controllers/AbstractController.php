<?php

declare(strict_types=1);

namespace Grav\Plugin\FlexObjects\Controllers;

use Grav\Common\Config\Config;
use Grav\Common\Grav;
use Grav\Common\Inflector;
use Grav\Common\Language\Language;
use Grav\Common\Session;
use Grav\Common\Utils;
use Grav\Framework\Controller\Traits\ControllerResponseTrait;
use Grav\Framework\Flex\FlexDirectory;
use Grav\Framework\Flex\Interfaces\FlexFormInterface;
use Grav\Framework\Flex\Interfaces\FlexObjectInterface;
use Grav\Framework\Psr7\Response;
use Grav\Framework\RequestHandler\Exception\NotFoundException;
use Grav\Framework\RequestHandler\Exception\PageExpiredException;
use Grav\Framework\Route\Route;
use Grav\Plugin\FlexObjects\Flex;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\Session\Message;

abstract class AbstractController implements RequestHandlerInterface
{
    use ControllerResponseTrait;

    /** @var string */
    protected $nonce_action = 'flex-object';

    /** @var string */
    protected $nonce_name = 'nonce';

    /** @var ServerRequestInterface */
    protected $request;

    /** @var Grav */
    protected $grav;

    /** @var string */
    protected $type;

    /** @var string */
    protected $key;

    /** @var FlexDirectory */
    protected $directory;

    /** @var FlexObjectInterface */
    protected $object;

    /**
     * Handle request.
     *
     * Fires event: flex.[directory].[task|action].[command]
     *
     * @param ServerRequestInterface $request
     * @return Response
     */
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $attributes = $request->getAttributes();
        $this->request = $request;
        $this->grav = $attributes['grav'] ?? Grav::instance();
        $this->type =  $attributes['type'] ?? null;
        $this->key =  $attributes['key'] ?? null;
        if ($this->type) {
            $this->directory = $this->getFlex()->getDirectory($this->type);
            $this->object = $attributes['object'] ?? null;
            if (!$this->object && $this->key && $this->directory) {
                $this->object = $this->directory->getObject($this->key) ?? $this->directory->createObject([], $this->key ?? '');
            }
        }

        /** @var Route $route */
        $route = $attributes['route'];
        $post = $this->getPost();

        if ($this->isFormSubmit()) {
            $form = $this->getForm();
            $this->nonce_name = $attributes['nonce_name'] ?? $form->getNonceName();
            $this->nonce_action = $attributes['nonce_action'] ?? $form->getNonceAction();
        }

        try {
            $task = $request->getAttribute('task') ?? $post['task'] ?? $route->getParam('task');
            if ($task) {
                if (empty($attributes['forwarded'])) {
                    $this->checkNonce($task);
                }
                $type = 'task';
                $command = $task;
            } else {
                $type = 'action';
                $command = $request->getAttribute('action') ?? $post['action'] ?? $route->getParam('action') ?? 'display';
            }
            $command = strtolower($command);

            $event = new Event(
                [
                    'controller' => $this,
                    'response' => null
                ]
            );

            $this->grav->fireEvent("flex.{$this->type}.{$type}.{$command}", $event);

            $response = $event['response'];
            if (!$response) {
                /** @var Inflector $inflector */
                $inflector = $this->grav['inflector'];
                $method = $type . $inflector::camelize($command);
                if ($method && method_exists($this, $method)) {
                    $response = $this->{$method}($request);
                } else {
                    throw new NotFoundException($request);
                }
            }
        } catch (\Exception $e) {
            $response = $this->createErrorResponse($e);
        }

        if ($response instanceof Response) {
            return $response;
        }

        return $this->createJsonResponse($response);
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @param string|null $name
     * @param mixed $default
     * @return mixed
     */
    public function getPost(string $name = null, $default = null)
    {
        $body = $this->request->getParsedBody();

        if ($name) {
            return $body[$name] ?? $default;
        }

        return $body;
    }

    public function isFormSubmit(): bool
    {
        return (bool)$this->getPost('__form-name__');
    }

    public function getForm(string $type = null): FlexFormInterface
    {
        $object = $this->getObject();
        if (!$object) {
            throw new \RuntimeException('Not Found', 404);
        }

        $formName = $this->getPost('__form-name__');
        $uniqueId = $this->getPost('__unique_form_id__') ?: $formName;

        $form = $object->getForm($type ?? 'edit');
        if ($uniqueId) {
            $form->setUniqueId($uniqueId);
        }

        return $form;
    }

    /**
     * @return Grav
     */
    public function getGrav(): Grav
    {
        return $this->grav;
    }

    /**
     * @return Session
     */
    public function getSession(): Session
    {
        return $this->grav['session'];
    }

    /**
     * @return Flex
     */
    public function getFlex(): Flex
    {
        return $this->grav['flex_objects'];
    }

    /**
     * @return string
     */
    public function getDirectoryType(): string
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getObjectKey(): string
    {
        return $this->key;
    }

    /**
     * @return FlexDirectory|null
     */
    public function getDirectory(): ?FlexDirectory
    {
        return $this->directory;
    }

    /**
     * @return FlexObjectInterface|null
     */
    public function getObject(): ?FlexObjectInterface
    {
        return $this->object;
    }

    /**
     * @param string $string
     * @return string
     */
    public function translate(string $string): string
    {
        /** @var Language $language */
        $language = $this->grav['language'];

        return $language->translate($string);
    }

    /**
     * @param string $message
     * @param string $type
     * @return $this
     */
    public function setMessage(string $message, string $type = 'info'): self
    {
        /** @var Message $messages */
        $messages = $this->grav['messages'];
        $messages->add($message, $type);

        return $this;
    }

    /**
     * @return Config
     */
    protected function getConfig(): Config
    {
        return $this->grav['config'];
    }

    /**
     * @param string $task
     * @throws PageExpiredException
     */
    protected function checkNonce(string $task): void
    {
        $nonce = null;

        if (\in_array(strtoupper($this->request->getMethod()), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $nonce = $this->getPost($this->nonce_name);
        }

        if (!$nonce) {
            $nonce = $this->grav['uri']->param($this->nonce_name);
        }

        if (!$nonce) {
            $nonce = $this->grav['uri']->query($this->nonce_name);
        }

        if (!$nonce || !Utils::verifyNonce($nonce, $this->nonce_action)) {
            throw new PageExpiredException($this->request);
        }
    }
}
