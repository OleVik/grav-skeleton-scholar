<?php

namespace Grav\Plugin\FlexObjects\Admin;

use Grav\Common\Cache;
use Grav\Common\Config\Config;
use Grav\Common\Debugger;
use Grav\Common\Filesystem\Folder;
use Grav\Common\Grav;
use Grav\Common\Page\Interfaces\PageInterface;
use Grav\Common\Plugin;
use Grav\Common\Uri;
use Grav\Common\User\Interfaces\UserInterface;
use Grav\Common\Utils;
use Grav\Framework\Controller\Traits\ControllerResponseTrait;
use Grav\Framework\File\Formatter\YamlFormatter;
use Grav\Framework\File\Interfaces\FileFormatterInterface;
use Grav\Framework\Flex\FlexDirectory;
use Grav\Framework\Flex\FlexForm;
use Grav\Framework\Flex\FlexFormFlash;
use Grav\Framework\Flex\Interfaces\FlexCollectionInterface;
use Grav\Framework\Flex\Interfaces\FlexFormInterface;
use Grav\Framework\Flex\Interfaces\FlexObjectInterface;
use Grav\Framework\Flex\Interfaces\FlexTranslateInterface;
use Grav\Framework\Object\Interfaces\ObjectInterface;
use Grav\Framework\Psr7\Response;
use Grav\Framework\RequestHandler\Exception\RequestException;
use Grav\Framework\Route\Route;
use Grav\Framework\Route\RouteFactory;
use Grav\Plugin\Admin\Admin;
use Grav\Plugin\FlexObjects\Controllers\MediaController;
use Grav\Plugin\FlexObjects\Flex;
use Nyholm\Psr7\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RocketTheme\Toolbox\Event\Event;
use RocketTheme\Toolbox\Session\Message;

/**
 * Class AdminController
 * @package Grav\Plugin\FlexObjects
 */
class AdminController
{
    use ControllerResponseTrait;

    /** @var Grav */
    public $grav;

    /** @var string */
    public $view;

    /** @var string */
    public $task;

    /** @var string */
    public $route;

    /** @var array */
    public $post;

    /** @var array|null */
    public $data;

    /** @var array */
    public $menu;

    /** @var Uri */
    protected $uri;

    /** @var Admin */
    protected $admin;

    /** @var string */
    protected $redirect;

    /** @var int */
    protected $redirectCode;

    /** @var Route */
    protected $currentRoute;

    /** @var Route */
    protected $referrerRoute;

    protected $action;
    protected $location;
    protected $target;
    protected $id;
    protected $active;
    protected $object;
    protected $collection;
    protected $directory;

    protected $nonce_name = 'admin-nonce';
    protected $nonce_action = 'admin-form';

    protected $task_prefix = 'task';
    protected $action_prefix = 'action';

    /**
     * Delete Directory
     */
    public function taskDefault()
    {
        $object = $this->getObject();
        $type = $this->target;
        $key = $this->id;

        $directory = $this->getDirectory($type);

        if ($object && $object->exists()) {
            $event = new Event(
                [
                    'type' => $type,
                    'key' => $key,
                    'admin' => $this->admin,
                    'flex' => $this->getFlex(),
                    'directory' => $directory,
                    'object' => $object,
                    'data' => $this->data,
                    'redirect' => $this->redirect
                ]
            );

            try {
                $grav = Grav::instance();
                $grav->fireEvent('onFlexTask' . ucfirst($this->task), $event);
            } catch (\Exception $e) {
                /** @var Debugger $debugger */
                $debugger = $this->grav['debugger'];
                $debugger->addException($e);
                $this->admin->setMessage($e->getMessage(), 'error');
            }

            $redirect = $event['redirect'];
            if ($redirect) {
                $this->setRedirect($redirect);
            }

            return $event->isPropagationStopped();
        }

        return false;
    }

    /**
     * Delete Directory
     */
    public function actionDefault()
    {
        $object = $this->getObject();
        $type = $this->target;
        $key = $this->id;

        $directory = $this->getDirectory($type);

        if ($object && $object->exists()) {
            $event = new Event(
                [
                    'type' => $type,
                    'key' => $key,
                    'admin' => $this->admin,
                    'flex' => $this->getFlex(),
                    'directory' => $directory,
                    'object' => $object,
                    'redirect' => $this->redirect
                ]
            );

            try {
                $grav = Grav::instance();
                $grav->fireEvent('onFlexAction' . ucfirst($this->action), $event);
            } catch (\Exception $e) {
                /** @var Debugger $debugger */
                $debugger = $this->grav['debugger'];
                $debugger->addException($e);
                $this->admin->setMessage($e->getMessage(), 'error');
            }

            $redirect = $event['redirect'];
            if ($redirect) {
                $this->setRedirect($redirect);
            }

            return $event->isPropagationStopped();
        }

        return false;
    }

    public function actionList()
    {
        /** @var Uri $uri */
        $uri = $this->grav['uri'];
        if ($uri->extension() === 'json') {
            $directory = $this->getDirectory();

            $options = [
                'collection' => $this->getCollection(),
                'url' => $uri->path(),
                'page' => $uri->query('page'),
                'limit' => $uri->query('per_page'),
                'sort' => $uri->query('sort'),
                'search' => $uri->query('filter'),
                'filters' => $uri->query('filters'),
            ];

            $table = $this->getFlex()->getDataTable($directory, $options);

            $response = $this->createJsonResponse($table->jsonSerialize());

            $this->close($response);
        }
    }

    public function actionCsv()
    {
        $collection = $this->getCollection();
        if (!$collection) {
            throw new \RuntimeException('Internal Error', 500);
        }
        if (!$collection->isAuthorized('list')) {
            throw new \RuntimeException($this->admin::translate('PLUGIN_ADMIN.INSUFFICIENT_PERMISSIONS_FOR_TASK') . ' list.', 403);
        }

        $config = $collection->getFlexDirectory()->getConfig('admin.export', false);
        if (!$config || empty($config['enabled'])) {
            throw new \RuntimeException($this->admin::translate('Not Found'), 404);
        }

        $method = $config['method'] ?? 'csvSerialize';
        $class = $config['formatter']['class'] ?? 'Grav\Framework\File\Formatter\CsvFormatter';
        if (!class_exists($class)) {
            throw new \RuntimeException($this->admin::translate('Formatter Not Found'), 404);
        }
        /** @var FileFormatterInterface $formatter */
        $formatter = new $class($config['formatter']['options'] ?? []);
        $filename = ($config['filename'] ?? 'export') . $formatter->getDefaultFileExtension();

        if (method_exists($collection, $method)) {
            $list = $collection->{$method}();
        } else {
            $list = [];

            /** @var ObjectInterface $object */
            foreach ($collection as $object) {
                if (method_exists($object, $method)) {
                    $data = $object->{$method}();
                    if ($data) {
                        $list[] = $data;
                    }
                } else {
                    $list[] = $object->jsonSerialize();
                }
            }
        }

        $response = new Response(
            200,
            [
                'Content-Type' => $formatter->getMimeType(),
                'Content-Disposition' => 'inline; filename="' . $filename . '"',
                'Expires' => 'Mon, 26 Jul 1997 05:00:00 GMT',
                'Last-Modified' => gmdate('D, d M Y H:i:s') . ' GMT',
                'Cache-Control' => 'no-store, no-cache, must-revalidate',
                'Pragma' => 'no-cache',
            ],
            $formatter->encode($list)
        );

        $this->close($response);
    }

    /**
     * Delete Directory
     */
    public function taskDelete()
    {
        try {
            $object = $this->getObject();

            if ($object && $object->exists()) {
                if (!$object->isAuthorized('delete')) {
                    throw new \RuntimeException($this->admin::translate('PLUGIN_ADMIN.INSUFFICIENT_PERMISSIONS_FOR_TASK') . ' delete.', 403);
                }

                $object->delete();

                $this->admin->setMessage($this->admin::translate(['PLUGIN_ADMIN.REMOVED_SUCCESSFULLY', 'Directory Entry']), 'info');

                $redirect = $this->referrerRoute->toString(true);
                if ($this->currentRoute === $this->referrerRoute) {
                    $redirect = dirname($this->currentRoute->toString(true));
                }

                $this->setRedirect($redirect);

                $grav = Grav::instance();
                $grav->fireEvent('onFlexAfterDelete', new Event(['type' => 'flex', 'object' => $object]));
                $grav->fireEvent('gitsync');
            }
        } catch (\RuntimeException $e) {
            $this->admin->setMessage('Delete Failed: ' . $e->getMessage(), 'error');

            $this->setRedirect($this->referrerRoute->toString(true), 302);
        }

        return $object ? true : false;
    }

    /**
     * Create a new empty folder (from modal).
     *
     * TODO: Pages
     */
    public function taskSaveNewFolder()
    {
        $directory = $this->getDirectory();
        if (!$directory) {
            throw new \RuntimeException('Not Found', 404);
        }

        if (!$directory->isAuthorized('create')) {
            throw new \RuntimeException($this->admin::translate('PLUGIN_ADMIN.INSUFFICIENT_PERMISSIONS_FOR_TASK') . ' save.', 403);
        }

        $data = $this->data;

        if ($data['route'] === '' || $data['route'] === '/') {
            $path = $this->grav['locator']->findResource('page://');
        } else {
            $path = $this->grav['page']->find($data['route'])->path();
        }

        $orderOfNewFolder = ''; //static::getNextOrderInFolder($path) . '.';
        $new_path         = $path . '/' . $orderOfNewFolder . $data['folder'];

        Folder::create($new_path);
        Cache::clearCache('invalidate');

        $this->grav->fireEvent('onAdminAfterSaveAs', new Event(['path' => $new_path]));

        $this->admin->setMessage($this->admin::translate('PLUGIN_ADMIN.SUCCESSFULLY_SAVED'), 'info');

        $this->setRedirect($this->referrerRoute->toString(true));
    }

    /**
     * Create a new object (from modal).
     *
     * TODO: Pages
     */
    public function taskContinue()
    {
        $directory = $this->getDirectory();
        if (!$directory) {
            throw new \RuntimeException('Not Found', 404);
        }

        if (!$directory->isAuthorized('create')) {
            throw new \RuntimeException($this->admin::translate('PLUGIN_ADMIN.INSUFFICIENT_PERMISSIONS_FOR_TASK') . ' save.', 403);
        }

        $this->data['route'] = '/' . trim($this->data['route'] ?? '', '/');
        $route = trim($this->data['route'], '/');
        $folder = $this->data['folder'] ?? 'undefined';
        if (isset($this->data['title'])) {
            $this->data['header']['title'] = $this->data['title'];
            unset($this->data['title']);
        }

        if (isset($this->data['name']) && strpos($this->data['name'], 'modular/') === 0) {
            $this->data['header']['body_classes'] = 'modular';
            if ($folder[0] !== '_') {
                $folder = '_' . $folder;
                $this->data['folder'] = $folder;
            }
        }
        unset($this->data['blueprint']);
        $key = trim("{$route}/{$folder}", '/');

        /*
        if (isset($data['visible'])) {
            if ($data['visible'] === '' || $data['visible']) {
                // if auto (ie '')
                $pageParent = $page->parent();
                $children = $pageParent ? $pageParent->children() : [];
                foreach ($children as $child) {
                    if ($child->order()) {
                        // set page order
                        $page->order(AdminController::getNextOrderInFolder($pageParent->path()));
                        break;
                    }
                }
            }
            if ((int)$data['visible'] === 1 && !$page->order()) {
                $header['visible'] = $data['visible'];
            }
        }
         */

        $formatter = new YamlFormatter();
        $this->data['frontmatter'] = $formatter->encode($this->data['header'] ?? []);
        $this->data['lang'] = $this->getLanguage();

        $this->object = $directory->createObject($this->data, $key);

        /** @var FlexForm $form */
        $form = $this->object->getForm();

        // Reset form, we are starting from scratch.
        $form->reset();

        /** @var FlexFormFlash $flash */
        $flash = $form->getFlash();
        $flash->setUrl($this->getFlex()->adminRoute($this->object));
        $flash->save(true);

        // Store the name and route of a page, to be used pre-filled defaults of the form in the future
        $this->admin->session()->lastPageName  = $this->data['name'] ?? '';
        $this->admin->session()->lastPageRoute = $this->data['route'] ?? '';

        $this->setRedirect($flash->getUrl());
    }

    /**
     * Save page as a new copy.
     *
     * Route: /pages
     *
     * @return bool True if the action was performed.
     * @throws \RuntimeException
     * @TODO: Pages
     */
    protected function taskCopy()
    {
        try {
            $object = $this->getObject();
            if (!$object || !$object->exists()) {
                throw new \RuntimeException('Not Found', 404);
            }

            if (!$object->isAuthorized('create')) {
                throw new \RuntimeException($this->admin::translate('PLUGIN_ADMIN.INSUFFICIENT_PERMISSIONS_FOR_TASK') . ' copy.',
                    403);
            }

            $object = $object->createCopy();

            $this->setRedirect($this->getFlex()->adminRoute($object));

        } catch (\RuntimeException $e) {
            $this->admin->setMessage('Copy Failed: ' . $e->getMessage(), 'error');
            $this->setRedirect($this->referrerRoute->toString(true), 302);
        }

        return true;
    }

        /**
         * $data['route'] = $this->grav['uri']->param('route');
         * $data['sortby'] = $this->grav['uri']->param('sortby', null);
         * $data['filters'] = $this->grav['uri']->param('filters', null);
         * $data['page'] $this->grav['uri']->param('page', true);
         * $data['base'] = $this->grav['uri']->param('base');
         * $initial = (bool) $this->grav['uri']->param('initial');
         *
         * @return ResponseInterface
         * @throws RequestException
         * @TODO: Pages
         */
    protected function actionGetLevelListing(): ResponseInterface
    {
        /** @var PageInterface|FlexObjectInterface $object */
        $object = $this->getObject($this->id ?? '');

        if (!$object || !method_exists($object, 'getLevelListing')) {
            throw new \RuntimeException('Not Found', 404);
        }

        $request = $this->getRequest();
        $data = $request->getParsedBody();

        if (!isset($data['field'])) {
            throw new RequestException($request, 'Bad Request', 400);
        }

        // Base64 decode the route
        $data['route'] = isset($data['route']) ? base64_decode($data['route']) : null;
        $data['filters'] = json_decode($options['filters'] ?? '{}', true) + ['type' => ['root', 'dir']];

        $initial = $data['initial'] ?? null;
        if ($initial) {
            $data['leaf_route'] = $data['route'];
            $data['route'] = null;
            $data['level'] = 1;
        }

        [$status, $message, $response,] = $object->getLevelListing($data);

        $json = [
            'status'  => $status,
            'message' => $this->admin::translate($message ?? 'PLUGIN_ADMIN.NO_ROUTE_PROVIDED'),
            'data' => array_values($response)
        ];

        return $this->createJsonResponse($json, 200);
    }

    /**
     * $data['route'] = $this->grav['uri']->param('route');
     * $data['sortby'] = $this->grav['uri']->param('sortby', null);
     * $data['filters'] = $this->grav['uri']->param('filters', null);
     * $data['page'] $this->grav['uri']->param('page', true);
     * $data['base'] = $this->grav['uri']->param('base');
     * $initial = (bool) $this->grav['uri']->param('initial');
     *
     * @return ResponseInterface
     * @throws RequestException
     * @TODO: Pages
     */
    protected function actionListLevel(): ResponseInterface
    {
        try {
            /** @var PageInterface|FlexObjectInterface $object */
            $object = $this->getObject('');

            if (!$object || !method_exists($object, 'getLevelListing')) {
                throw new \RuntimeException('Not Found', 404);
            }

            $directory = $object->getFlexDirectory();
            if (!$directory->isAuthorized('list')) {
                throw new \RuntimeException($this->admin::translate('PLUGIN_ADMIN.INSUFFICIENT_PERMISSIONS_FOR_TASK') . ' getLevelListing.',
                    403);
            }

            $request = $this->getRequest();
            $data = $request->getParsedBody();

            // Base64 decode the route
            $data['route'] = isset($data['route']) ? base64_decode($data['route']) : null;
            $data['filters'] = ($data['filters'] ?? []) + ['type' => ['dir']];
            $data['lang'] = $this->getLanguage();

            $initial = $data['initial'] ?? null;
            if ($initial) {
                $data['leaf_route'] = $data['route'];
                $data['route'] = null;
                $data['level'] = 1;
            }

            [$status, $message, $response,] = $object->getLevelListing($data);

            $json = [
                'status'  => $status,
                'message' => $this->admin::translate($message ?? 'PLUGIN_ADMIN.NO_ROUTE_PROVIDED'),
                'route' => $data['route'] ?? $data['leaf_route'] ?? null,
                'initial' => (bool)$initial,
                'data' => array_values($response)
            ];
        } catch (\Exception $e) {
            return $this->createErrorResponse($e);
        }

        return $this->createJsonResponse($json, 200);
    }

    public function taskSaveas()
    {
        return $this->taskSave();
    }

    public function taskSave()
    {
        $key = $this->id;

        try {
            $object = $this->getObject($key);
            if (!$object) {
                throw new \RuntimeException('Not Found', 404);
            }

            if ($object->exists()) {
                if (!$object->isAuthorized('update')) {
                    throw new \RuntimeException($this->admin::translate('PLUGIN_ADMIN.INSUFFICIENT_PERMISSIONS_FOR_TASK') . ' save.',
                        403);
                }
            } else {
                if (!$object->isAuthorized('create')) {
                    throw new \RuntimeException($this->admin::translate('PLUGIN_ADMIN.INSUFFICIENT_PERMISSIONS_FOR_TASK') . ' save.',
                        403);
                }
            }
            $grav = Grav::instance();

            /** @var ServerRequestInterface $request */
            $request = $grav['request'];

            /** @var FlexForm $form */
            $form = $this->getForm($object);
            $form->handleRequest($request);
            $error = $form->getError();
            $errors = $form->getErrors();
            if ($errors) {
                if ($error) {
                    $this->admin->setMessage($error, 'error');
                }

                foreach ($errors as $field => $list) {
                    foreach ((array)$list as $message) {
                        $this->admin->setMessage($message, 'error');
                    }
                }
                throw new \RuntimeException('Form validation failed, please check your input');
            }
            if ($error) {
                throw new \RuntimeException($error);
            }

            $object = $form->getObject();

            $this->admin->setMessage($this->admin::translate('PLUGIN_ADMIN.SUCCESSFULLY_SAVED'), 'info');

            if (!$this->redirect) {
                // TODO: remove 'action:add' after save.
                if ($this->referrerRoute->getGravParam('action') === 'add') {
                    $this->referrerRoute = $this->currentRoute->withGravParam('action', null);
                    if (!Utils::endsWith($this->referrerRoute->toString(false), '/' . $object->getKey())) {
                        $this->referrerRoute = $this->referrerRoute->withAddedPath($object->getKey());
                    }
                } elseif ($key !== $object->getKey()) {
                    $this->referrerRoute = $this->currentRoute->withRoute($this->currentRoute->getRoute(0, -1) . '/' . $object->getKey());
                }
                $postAction = $request->getParsedBody()['data']['_post_entries_save'] ?? 'edit';
                if ($postAction === 'list') {
                    $this->referrerRoute = $this->currentRoute->withRoute($this->currentRoute->getRoute(0, -1));
                }

                $lang = null;
                if ($object instanceof FlexTranslateInterface) {
                    $lang = $object->getLanguage();
                    $this->referrerRoute = $this->referrerRoute->withLanguage($lang);
                }

                $this->setRedirect($this->referrerRoute->toString(true));
            }

            $grav = Grav::instance();
            $grav->fireEvent('onFlexAfterSave', new Event(['type' => 'flex', 'object' => $object]));
            $grav->fireEvent('gitsync');
        } catch (\RuntimeException $e) {
            $this->admin->setMessage('Save Failed: ' . $e->getMessage(), 'error');
            $this->setRedirect($this->referrerRoute->toString(true), 302);
        }

        return true;
    }

    public function taskMediaList()
    {
        try {
            $response = $this->forwardMediaTask('action', 'media.list');

            $this->admin->json_response = json_decode($response->getBody(), false);
        } catch (\Exception $e) {
            die($e->getMessage());
        }

        return true;
    }

    public function taskMediaUpload()
    {
        try {
            $response = $this->forwardMediaTask('task', 'media.upload');

            $this->admin->json_response = json_decode($response->getBody(), false);
        } catch (\Exception $e) {
            die($e->getMessage());
        }

        return true;
    }

    public function taskMediaDelete()
    {
        try {
            $response = $this->forwardMediaTask('task', 'media.delete');

            $this->admin->json_response = json_decode($response->getBody(), false);
        } catch (\Exception $e) {
            die($e->getMessage());
        }

        return true;
    }

    public function taskListmedia()
    {
        return $this->taskMediaList();
    }

    public function taskAddmedia()
    {
        return $this->taskMediaUpload();
    }

    public function taskDelmedia()
    {
        return $this->taskMediaDelete();
    }

    public function taskFilesUpload()
    {
        throw new \RuntimeException('Task delMedia should not be called!');
    }

    public function taskRemoveMedia($filename = null)
    {
        throw new \RuntimeException('Task removeMedia should not be called!');
    }

    public function taskGetFilesInFolder()
    {
        try {
            $response = $this->forwardMediaTask('action', 'media.picker');

            $this->admin->json_response = json_decode($response->getBody(), false);
        } catch (\Exception $e) {
            $this->admin->json_response = ['success' => false, 'error' => $e->getMessage()];
        }

        return true;
    }

    protected function forwardMediaTask(string $type, string $name)
    {
        $route = Uri::getCurrentRoute()->withGravParam('task', null)->withGravParam($type, $name);
        $object = $this->getObject();

        /** @var ServerRequest $request */
        $request = $this->grav['request'];
        $request = $request
            ->withAttribute('type', $this->target)
            ->withAttribute('key', $this->id)
            ->withAttribute('storage_key', $object && $object->exists() ? $object->getStorageKey() : null)
            ->withAttribute('route', $route)
            ->withAttribute('forwarded', true)
            ->withAttribute('object', $object);

        $controller = new MediaController();

        return $controller->handle($request);
    }

    /**
     * @return Flex
     */
    protected function getFlex()
    {
        return Grav::instance()['flex_objects'];
    }

    /**
     * @param Plugin   $plugin
     */
    public function __construct(Plugin $plugin)
    {
        $this->grav = Grav::instance();
        $this->active = false;

        // Ensure the controller should be running
        if (Utils::isAdminPlugin()) {
            list(, $location, $target) = $this->grav['admin']->getRouteDetails();

            $menu = $plugin->getAdminMenu();

            // return null if this is not running
            if (!isset($menu[$location]))  {
                return;
            }

            $this->menu = $menu[$location];

            $directory = $menu[$location]['directory'] ?? '';
            $location = 'flex-objects';
            if ($directory) {
                $id = $target;
                $target = $directory;
            } else {
                $array = explode('/', $target, 2);
                $target = array_shift($array) ?: null;
                $id = array_shift($array) ?: null;
            }

            /** @var Uri $uri */
            $uri = $this->grav['uri'];

            // Post
            $post = $_POST ?? [];
            if (isset($post['data'])) {
                $this->data = $this->getPost($post['data']);
                unset($post['data']);
            }

            // Task
            $task = $this->grav['task'];
            if ($task) {
                $this->task = $task;
            }

            $this->post = $this->getPost($post);
            $this->location = $location;
            $this->target = $target;
            $this->id = $this->post['id'] ?? $id;
            $this->action = $this->post['action'] ?? $uri->param('action');
            $this->active = true;
            $this->admin = Grav::instance()['admin'];
            $this->currentRoute = $uri::getCurrentRoute();
            $referrer = $uri->referrer();
            $this->referrerRoute = $referrer ? RouteFactory::createFromString($referrer) : $this->currentRoute;
        }
    }

    /**
     * Performs a task or action on a post or target.
     *
     * @return bool|mixed
     */
    public function execute()
    {
        /** @var UserInterface $user */
        $user = $this->grav['user'];
        if (!$user->authorize('admin.login')) {
            // TODO: improve
            return false;
        }

        $params = [];

        $event = new Event(
            [
                'type' => &$this->target,
                'key' => &$this->id,
                'directory' => &$this->directory,
                'collection' => &$this->collection,
                'object' => &$this->object
            ]
        );
        $this->grav->fireEvent("flex.{$this->target}.admin.route", $event);

        if ($this->isFormSubmit()) {
            $form = $this->getForm();
            $this->nonce_name = $form->getNonceName();
            $this->nonce_action = $form->getNonceAction();
        }

        // Handle Task & Action
        if ($this->task) {
            // validate nonce
            if (!$this->validateNonce()) {
                $e = new RequestException($this->getRequest(), 'Page Expired', 400);

                $this->close($this->createErrorResponse($e));
            }
            $method = $this->task_prefix . ucfirst(str_replace('.', '', $this->task));

            if (!method_exists($this, $method)) {
                $method = $this->task_prefix . 'Default';
            }

        } elseif ($this->target) {
            if (!$this->action) {
                if ($this->id) {
                    $this->action = 'edit';
                    $params[] = $this->id;
                } else {
                    $this->action = 'list';
                }
            }
            $method = 'action' . ucfirst(strtolower(str_replace('.', '', $this->action)));

            if (!method_exists($this, $method)) {
                $method = $this->action_prefix . 'Default';
            }
        } else {
            return null;
        }

        if (!method_exists($this, $method)) {
            return null;
        }

        try {
            $response = $this->{$method}(...$params);
        } catch (RequestException $e) {
            $response = $this->createErrorResponse($e);
        } catch (\RuntimeException $e) {
            $response = null;
            $this->setMessage($e->getMessage(), 'error');
        }

        if ($response instanceof ResponseInterface) {
            $this->close($response);
        }

        // Grab redirect parameter.
        $redirect = $this->post['_redirect'] ?? null;
        unset($this->post['_redirect']);

        // Redirect if requested.
        if ($redirect) {
            $this->setRedirect($redirect);
        }

        return $response;
    }

    public function isFormSubmit(): bool
    {
        return (bool)($this->post['__form-name__'] ?? null);
    }

    public function getForm(FlexObjectInterface $object = null): FlexFormInterface
    {
        $object = $object ?? $this->getObject();
        if (!$object) {
            throw new \RuntimeException('Not Found', 404);
        }

        $formName = $this->post['__form-name__'] ?? '';
        $name = '';
        $uniqueId = null;

        // Get the form name. This defines the blueprint which is being used.
        if (strpos($formName, '-')) {
            $parts = explode('-', $formName);
            $prefix = $parts[0] ?? '';
            $type = $parts[1] ?? '';
            if ($prefix === 'flex' && $type === $object->getFlexType()) {
                $name = $parts[2] ?? '';
                if ($name === 'object') {
                    $name = '';
                }
                $uniqueId = $this->post['__unique_form_id__'] ?? null;
            }
        }

        $options = [
            'unique_id' => $uniqueId,
        ];

        return $object->getForm($name, $options);
    }

    /**
     * @param string|null $key
     * @return FlexObjectInterface|null
     */
    public function getObject(string $key = null): ?FlexObjectInterface
    {
        if (null === $this->object) {
            $key = $key ?? $this->id;
            $object = false;

            $directory = $this->getDirectory();
            if ($directory) {
                if (null !== $key) {
                    $object = $directory->getObject($key) ?? $directory->createObject([], $key);
                } elseif ($this->action === 'add') {
                    $object = $directory->createObject([], '');
                }

                if ($object instanceof FlexTranslateInterface && $this->isMultilang()) {
                    $language = $this->getLanguage();
                    if ($object->hasTranslation($language)) {
                        $object = $object->getTranslation($language);
                    }
                }
            }

            $this->object = $object;
        }

        return $this->object ?: null;
    }

    /**
     * @param string $type
     * @return FlexDirectory|null
     */
    public function getDirectory($type = null)
    {
        $type = $type ?? $this->target;

        if ($type && null === $this->directory) {
            $this->directory = Grav::instance()['flex_objects']->getDirectory($type);
        }

        return $this->directory;
    }

    public function getCollection(): ?FlexCollectionInterface
    {
        if (null === $this->collection) {
            $directory = $this->getDirectory();

            $this->collection = $directory ? $directory->getCollection() : null;
        }

        return $this->collection;
    }

    public function setMessage($msg, $type = 'info')
    {
        /** @var Message $messages */
        $messages = $this->grav['messages'];
        $messages->add($msg, $type);
    }

    public function isActive()
    {
        return (bool) $this->active;
    }

    public function setLocation($location)
    {
        $this->location = $location;
    }

    public function getLocation()
    {
        return $this->location;
    }

    public function setAction($action)
    {
        $this->action = $action;
    }

    public function getAction()
    {
        return $this->action;
    }

    public function setTask($task)
    {
        $this->task = $task;
    }

    public function getTask()
    {
        return $this->task;
    }

    public function setTarget($target)
    {
        $this->target = $target;
    }

    public function getTarget()
    {
        return $this->target;
    }

    public function setId($target)
    {
        $this->id = $target;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * Sets the page redirect.
     *
     * @param string $path The path to redirect to
     * @param int    $code The HTTP redirect code
     */
    public function setRedirect($path, $code = 303)
    {
        $this->redirect     = $path;
        $this->redirectCode = (int)$code;
    }

    /**
     * Redirect to the route stored in $this->redirect
     */
    public function redirect()
    {
        $this->admin->redirect($this->redirect, $this->redirectCode);
    }

    /**
     * Return true if multilang is active
     *
     * @return bool True if multilang is active
     */
    protected function isMultilang(): bool
    {
        return count($this->grav['config']->get('system.languages.supported', [])) > 1;
    }

    protected function validateNonce(): bool
    {
        $nonce_action = $this->nonce_action;
        $nonce = $this->post[$this->nonce_name] ?? $this->grav['uri']->param($this->nonce_name) ?? $this->grav['uri']->query($this->nonce_name);

        if (!$nonce) {
            $nonce = $this->post['admin-nonce'] ?? $this->grav['uri']->param('admin-nonce') ?? $this->grav['uri']->query('admin-nonce');
            $nonce_action = 'admin-form';
        }

        return $nonce && Utils::verifyNonce($nonce, $nonce_action);
    }

    /**
     * Prepare and return POST data.
     *
     * @param array $post
     *
     * @return array
     */
    protected function getPost($post): array
    {
        if (!is_array($post)) {
            return [];
        }

        unset($post['task']);

        // Decode JSON encoded fields and merge them to data.
        if (isset($post['_json'])) {
            $post = array_replace_recursive($post, $this->jsonDecode($post['_json']));
            unset($post['_json']);
        }

        $post = $this->cleanDataKeys($post);

        return $post;
    }

    protected function close(ResponseInterface $response): void
    {
        $this->grav->close($response);
    }

    /**
     * Recursively JSON decode data.
     *
     * @param  array $data
     *
     * @return array
     */
    protected function jsonDecode(array $data)
    {
        foreach ($data as &$value) {
            if (is_array($value)) {
                $value = $this->jsonDecode($value);
            } else {
                $value = json_decode($value, true);
            }
        }

        return $data;
    }

    protected function cleanDataKeys($source = []): array
    {
        $out = [];

        if (is_array($source)) {
            foreach ($source as $key => $value) {
                $key = str_replace(['%5B', '%5D'], ['[', ']'], $key);
                if (is_array($value)) {
                    $out[$key] = $this->cleanDataKeys($value);
                } else {
                    $out[$key] = $value;
                }
            }
        }

        return $out;
    }

    /**
     * @return string
     */
    public function getLanguage(): string
    {
        return $this->admin->language ?? '';
    }

    /**
     * @return Config
     */
    protected function getConfig(): Config
    {
        return $this->grav['config'];
    }

    /**
     * @return ServerRequestInterface
     */
    protected function getRequest(): ServerRequestInterface
    {
        /** @var ServerRequestInterface $request */
        return $this->grav['request'];
    }
}
