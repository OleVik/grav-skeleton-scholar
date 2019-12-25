<?php

declare(strict_types=1);

namespace Grav\Plugin\FlexObjects\Table;

use Grav\Common\Debugger;
use Grav\Common\Grav;
use Grav\Framework\Collection\CollectionInterface;
use Grav\Framework\Flex\Interfaces\FlexCollectionInterface;
use Grav\Framework\Flex\Interfaces\FlexObjectInterface;

/**
 * Class DataTable
 * @package Grav\Plugin\Gitea
 *
 * https://github.com/ratiw/vuetable-2/wiki/Data-Format-(JSON)
 * https://github.com/ratiw/vuetable-2/wiki/Sorting
 */
class DataTable implements \JsonSerializable
{
    /** @var string */
    private $url;
    /** @var int */
    private $limit;
    /** @var int */
    private $page;
    /** @var array */
    private $sort;
    /** @var string */
    private $search;
    /** @var FlexCollectionInterface */
    private $collection;
    /** @var FlexCollectionInterface */
    private $filteredCollection;
    /** @var array */
    private $columns;
    /** @var \Twig_Environment */
    private $twig;
    /** @var array */
    private $twig_context;

    public function __construct(array $params)
    {
        $this->setUrl($params['url'] ?? '');
        $this->setLimit((int)($params['limit'] ?? 10));
        $this->setPage((int)($params['page'] ?? 1));
        $this->setSort($params['sort'] ?? ['id' => 'asc']);
        $this->setSearch($params['search'] ?? '');
    }

    public function setUrl(string $url): void
    {
        $this->url = $url;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = max(1, $limit);
    }

    public function setPage(int $page): void
    {
        $this->page = max(1, $page);
    }

    public function setSort($sort)
    {
        if (is_string($sort)) {
            $sort = $this->decodeSort($sort);
        } elseif (!\is_array($sort)) {
            $sort = [];
        }

        $this->sort = $sort;
    }

    public function setSearch(string $search): void
    {
        $this->search = $search;
    }

    public function setCollection(CollectionInterface $collection): void
    {
        $this->collection = $collection;
        $this->filteredCollection = null;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLastPage(): int
    {
        return 1 + (int)floor(max(0, $this->getTotal()-1) / $this->getLimit());
    }

    public function getTotal(): int
    {
        $collection = $this->filteredCollection ?? $this->getCollection();

        return $collection ? $collection->count() : 0;
    }

    public function getSort(): array
    {
        return $this->sort;
    }

    public function getCollection(): ?FlexCollectionInterface
    {
        return $this->collection;
    }

    public function getUrl(int $page): ?string
    {
        if ($page < 1 || $page > $this->getLastPage()) {
            return null;
        }

        return "{$this->url}.json?page={$page}&per_page={$this->getLimit()}&sort={$this->encodeSort()}";
    }

    public function getColumns()
    {
        if (null === $this->columns) {
            $collection = $this->getCollection();
            if (!$collection) {
                return [];
            }

            $blueprint = $collection->getFlexDirectory()->getBlueprint();
            $schema = $blueprint->schema();

            $list = [];
            foreach ($blueprint->get('config/admin/list/fields') as $key => $options) {
                if (!isset($options['field'])) {
                    $options['field'] = $schema->get($options['alias'] ?? $key);
                }
                if (!$options['field'] || !empty($options['field']['ignore'])) {
                    continue;
                }
                $list[$key] = $options;
            }

            $this->columns = $list;
        }

        return $this->columns;
    }

    public function getData()
    {
        $grav = Grav::instance();

        /** @var Debugger $debugger */
        $debugger = $grav['debugger'];
        $debugger->startTimer('datatable', 'Data Table');

        $collection = $this->getCollection();
        if (!$collection) {
            return [];
        }
        if ($this->search !== '') {
            $collection = $collection->search($this->search);
        }

        $columns = $this->getColumns();

        $collection = $collection->sort($this->getSort());

        $this->filteredCollection = $collection;

        $limit = $this->getLimit();
        $page = $this->getPage();
        $to = $page * $limit;
        $from = $to - $limit + 1;

        if ($from < 1 || $from > $this->getTotal()) {
            $debugger->stopTimer('datatable');
            return [];
        }

        $array = $collection->slice($from-1, $limit);

        $twig = $grav['twig'];
        $grav->fireEvent('onTwigSiteVariables');

        $this->twig = $twig->twig;
        $this->twig_context = $twig->twig_vars;

        $list = [];
        /** @var FlexObjectInterface $object */
        foreach ($array as $object) {
            $item = [
                'id' => $object->getKey(),
                'timestamp' => $object->getTimestamp()
            ];
            foreach ($columns as $name => $column) {
                $item[str_replace('.', '_', $name)] = $this->renderColumn($name, $column, $object);
            }
            $item['_actions_'] = $this->renderActions($object);

            $list[] = $item;
        }

        $debugger->stopTimer('datatable');

        return $list;
    }

    public function jsonSerialize()
    {
        $data = $this->getData();
        $total = $this->getTotal();
        $limit = $this->getLimit();
        $page = $this->getPage();
        $to = $page * $limit;
        $from = $to - $limit + 1;

        $empty = empty($data);

        return [
            'links' => [
                'pagination' => [
                    'total' => $total,
                    'per_page' => $limit,
                    'current_page' => $page,
                    'last_page' => $this->getLastPage(),
                    'next_page_url' => $this->getUrl($page+1),
                    'prev_page_url' => $this->getUrl($page-1),
                    'from' => $empty ? null : $from,
                    'to' => $empty ? null : min($to, $total),
                ]
            ],
            'data' => $data
        ];
    }

    protected function renderColumn(string $name, array $column, FlexObjectInterface $object)
    {
        $grav = Grav::instance();
        $flex = $grav['flex_objects'];

        $value = $object->value($name) ?? $object->getNestedProperty($name, $column['field']['default'] ?? null);
        $type = $column['field']['type'] ?? 'text';
        $hasLink = $column['link'] ?? null;
        $link = null;
        if ($hasLink) {
            $route = $grav['route']->withExtension('');
            $link = $route->withAddedPath($object->getKey())->withoutParams()->getUri();
        }

        $template = $this->twig->resolveTemplate(["forms/fields/{$type}/edit_list.html.twig", 'forms/fields/text/edit_list.html.twig']);

        return $this->twig->load($template)->render([
            'value' => $value,
            'link' => $link,
            'field' => $column['field'],
            'object' => $object,
            'flex' => $flex,
            'route' => $grav['route']->withExtension('')
        ] + $this->twig_context);
    }

    protected function renderActions(FlexObjectInterface $object)
    {
        $grav = Grav::instance();
        $type = $object->getFlexType();
        $template = $this->twig->resolveTemplate(["flex-objects/types/{$type}/list_actions.html.twig", 'flex-objects/types/default/list_actions.html.twig']);

        return $this->twig->load($template)->render([
            'object' => $object,
            'flex' => $grav['flex_objects'],
            'route' => $grav['route']->withExtension('')
        ] + $this->twig_context);
    }

    protected function decodeSort(string $sort, $fieldSeparator = ',', $orderSeparator = '|')
    {
        $strings = explode($fieldSeparator, $sort);
        $list = [];
        foreach ($strings as $string) {
            $item = explode($orderSeparator, $string, 2);
            $key = array_shift($item);
            $order = array_shift($item) === 'desc' ? 'desc' : 'asc';
            $list[$key] = $order;
        }

        return $list;
    }

    protected function encodeSort($fieldSeparator = ',', $orderSeparator = '|')
    {
        $list = [];
        foreach ($this->getSort() as $key => $order) {
            $list[] = $key . $orderSeparator . ($order ?: 'asc');
        }

        return implode($fieldSeparator, $list);
    }
}
