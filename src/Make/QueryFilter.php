<?php

namespace Saidqb\LaravelSupport\Make;

use Saidqb\LaravelSupport\Concerns\HasHelper;

class QueryFilter
{
    use HasHelper;

    public $defaultLimit = 999999999;

    public $queryBuilder = null;
    public $tableSelectAs = [];
    public $defaultRequest = [
        'page' => 1,
        'limit' => 10,
        'order_by' => 'DESC',
        'sort' => [],
        'search' => '',
    ];


    public $query;
    public $select;
    public $search;
    public $request;


    static function make()
    {
        return new static();
    }

    public function queryPaginateCustom($total, $pagenum, $limit)
    {
        $total_page = ceil($total / $limit);

        //------------- Prev page
        $prev = $pagenum - 1;
        if ($prev < 1) {
            $prev = 0;
        }
        //------------------------

        //------------- Next page
        $next = $pagenum + 1;
        if ($next > $total_page) {
            $next = 0;
        }
        //----------------------

        $from = 1;
        $to = $total_page;

        $to_page = $pagenum - 2;
        if ($to_page > 0) {
            $from = $to_page;
        }

        if ($total_page >= 5) {
            if ($total_page > 0) {
                $to = 5 + $to_page;
                if ($to > $total_page) {
                    $to = $total_page;
                }
            } else {
                $to = 5;
            }
        }

        #looping kotak pagination
        $firstpage_istrue = false;
        $lastpage_istrue = false;
        $detail = [];
        if ($total_page <= 1) {
            $detail = [];
        } else {
            for ($i = $from; $i <= $to; $i++) {
                $detail[] = $i;
            }
            if ($from != 1) {
                $firstpage_istrue = true;
            }
            if ($to != $total_page) {
                $lastpage_istrue = true;
            }
        }

        $total_display = 0;
        if ($pagenum < $total_page) {
            $total_display = $limit;
        }
        if ($pagenum == $total_page) {
            if (($total % $limit) != 0) {
                $total_display = $total % $limit;
            } else {
                $total_display = $limit;
            }
        }
        if ($limit == $this->defaultLimit) {
            $limit = $total;
        }
        $pagination = array(
            'total_data' => $total,
            'total_page' => $total_page,
            'total_display' => $total_display,
            'first_page' => $firstpage_istrue,
            'last_page' => $lastpage_istrue,
            'prev' => $prev,
            'current' => $pagenum,
            'limit' => (int)$limit,
            'next' => $next,
            'detail' => $detail
        );

        return $pagination;
    }


    public function queryPaginateGenerate($res, $laravel = false)
    {
        if ($laravel === true) {

            if ($res->perPage() == $this->defaultLimit) {
                $limit = $res->total();
            }

            $showPage = 5;
            $pagination['count'] = $res->count();
            $pagination['currentPage'] = $res->currentPage();
            $pagination['firstItem'] = static::emptyVal($res->firstItem(), 0);
            $pagination['hasPages'] = $res->hasPages();
            $pagination['hasMorePages'] = $res->hasMorePages();
            $pagination['lastItem'] = static::emptyVal($res->lastItem(), 0);
            $pagination['lastPage'] = $res->lastPage();
            $pagination['nextPageUrl'] = static::emptyVal($res->nextPageUrl(), '');
            $pagination['onFirstPage'] = $res->onFirstPage();
            $pagination['perPage'] = $res->perPage();
            $pagination['previousPageUrl'] = static::emptyVal($res->previousPageUrl());
            $pagination['total'] = $res->total();
            $pagination['getPageName'] = $res->getPageName();
            $pagination['showPage'] = $showPage;

            $from = $res->currentPage();
            $to = $from + $pagination['showPage'] - 1;
            $end = $res->lastPage();
            $detail = [];

            if ($res->total() <= 1) {
                $detail = [];
            } else {
                for ($i = $from; $i <= $to; $i++) {
                    if ($end >= $i) {
                        $detail[] = $i;
                    }
                }
            }
            $pagination['pages'] = $detail;
            return $pagination;
        } else {

            $queryPaginateCustom = self::queryPaginateCustom($res->total(), $res->currentPage(), $res->perPage());
            return $queryPaginateCustom;
        }
    }

    /* get data db $query = DB:table()*/
    /**
     * @param $request
     * @param $query
     * @param $setFilter
     */
    public function queryBuilder($request, $query, $setFilter)
    {
        $this->query = $query;
        $this->select = !isset($setFilter['select']) ? ['*'] : $setFilter['select'];
        $this->search = !isset($setFilter['search']) ? [] : $setFilter['search'];
        $this->request = $request;
    }

    public function query($query = null)
    {
        $this->query = $query;
        return $this;
    }

    public function search($search = [])
    {
        $this->search = $search;
        return $this;
    }

    public function select($select = [])
    {
        $this->select = $select;
        return $this;
    }

    public function request($request = [])
    {
        $this->request = $request;
        return $this;
    }

    public function get()
    {
        $query = $this->query;
        $req = $this->request;

        $defaultData = [
            'order_by' => ['asc', 'desc'],
        ];

        $defaultRequest = $this->defaultRequest;


        $req = array_merge($defaultRequest, $req);

        foreach ($req as $k => $v) {
            if (empty($v)) {
                if (isset($defaultRequest[$k])) {
                    $req[$k] = $defaultRequest[$k];
                }
            }
        }

        if (!is_numeric($req['limit'])) {
            $req['limit'] = $defaultRequest['limit'];
        }

        if ($req['limit'] == -1) {
            $req['limit'] = static::$defaultLimit;
        }

        $columns = [];
        foreach ($this->select as $key => $v) {
            $v = trim($v);
            if (strpos($v, ' as ') !== false) {
                $vArr = explode(' as ', $v);
                $v = trim($vArr[1]);
            }
            $columns[] = $v;
        };

        if (count($columns) == 1 && isset($columns[0]) && $columns[0] == '*') {
        } else {
            $this->tableSelectAs = $columns;
        }


        if (!empty($this->tableSelectAs)) {
            $query->where(function ($query) {
                $this->query = $query;
                $query = self::filterQuery();
            });
        }


        if (!empty($this->search) && !empty($req['search'])) {
            $query->where(function ($query) use ($req) {
                foreach ($this->search as $key => $v) {
                    $query->orWhere($v, 'LIKE', "%{$req['search']}%");
                }
            });
        }

        if (!empty($req['sort'])) {
            if (is_array($req['sort']) && !empty($req['sort'])) {
                foreach ($req['sort'] as $k => $v) {
                    if (in_array($k, $this->tableSelectAs) && in_array(strtolower($v), $defaultData['order_by'])) {
                        $query->orderBy($k, $v);
                    }
                }
            } else {
                if (static::startsWith($req['sort'], '-')) {
                    $columnsSort = substr($req['sort'], 1);
                    if (in_array($columnsSort, $this->tableSelectAs)) {
                        $query->orderBy($columnsSort, 'desc');
                    }
                } else {
                    $columnsSort = $req['sort'];
                    if (in_array($columnsSort, $this->tableSelectAs)) {
                        $query->orderBy($req['sort'], 'asc');
                    }
                }
            }
        }

        $items = $query->paginate($req['limit'], $this->select);


        $content['items'] = $items->items();
        $content['pagination'] = $this->queryPaginateGenerate($items, false);
        return $content;
    }

    /**
     * Filter Query
     */
    public function filterQuery()
    {
        $query = $this->query;
        $tableSelectAs = $this->tableSelectAs;

        foreach ($this->request as $field => $value) {
            if (in_array($field, $tableSelectAs)) {
                if (is_array($value)) {
                    foreach ($value as $comparison => $val) {
                        if ($val !== '') {
                            switch ($comparison) {
                                case 'eq':
                                    $query->where($field, '=', $val);
                                    break;

                                case 'neq':
                                    $query->where($field, '!=', $val);
                                    break;

                                case 'lt':
                                    $query->where($field, '<', $val);
                                    break;

                                case 'gt':
                                    $query->where($field, '>', $val);
                                    break;

                                case 'lte':
                                    $query->where($field, '<=', $val);
                                    break;

                                case 'gte':
                                    $query->where($field, '>=', $val);
                                    break;

                                case 'le':
                                    $query->where($field, 'like', "$val%");
                                    break;

                                case 'ls':
                                    $query->where($field, 'like', "%$val");
                                    break;

                                case 'lse':
                                    $query->where($field, 'like', "%$val%");
                                    break;

                                case 'in':
                                    $val = !is_array($val) ? explode(',', $val) : $val;
                                    $query->whereIn($field, $val);
                                    break;

                                case 'nin':
                                    $val = !is_array($val) ? explode(',', $val) : $val;
                                    $query->whereNotIn($field, $val);
                                    break;
                            }
                        }
                    }
                } else {
                    if ($value !== '') {
                        $query->where($field, '=', $value);
                    }
                }
            }
        }
        return $query;
    }
}
