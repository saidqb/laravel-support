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

    public $setPaginationType = 'default';


    static function make()
    {
        return new static();
    }

    public function paginationType($type = 'default')
    {
        $this->setPaginationType = $type;
        return $this;
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


    public function queryPaginateGenerate($res)
    {

        if ($this->setPaginationType === 'default') {

            if ($res->perPage() == $this->defaultLimit) {
                $limit = $res->total();
            }

            $showPage = 5;
            $pagination['total_data'] = $res->total();
            $pagination['total_display'] = $res->count();
            $pagination['current_page'] = $res->currentPage();
            $pagination['total_page'] = $res->lastPage();
            $pagination['limit'] = $res->perPage();
            $pagination['start'] = static::emptyVal($res->firstItem(), 0);
            $pagination['end'] = static::emptyVal($res->lastItem(), 0);

            $url = $res->nextPageUrl();
            $pagination['next_page_url'] = 0;
            if ($url) {
                $url_components = parse_url($url);
                parse_str($url_components['query'], $params);
                $pagination['next_page_url'] = (int) $params['page'];
            }

            $url = $res->previousPageUrl();
            $pagination['prev_page_url'] = $res->lastPage();
            if ($url) {
                $url_components = parse_url($url);
                parse_str($url_components['query'], $params);
                $pagination['prev_page_url'] = (int) $params['page'];
            }


            $from = $res->currentPage();
            $to = $from + $showPage - 1;
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

        foreach ($this->select as $key => $v) {
            $v = trim($v);
            if (strpos($v, ' as ') !== false) {
                $vArr = explode(' as ', $v);
                $v = trim($vArr[1]);
                $this->tableSelectAs[$v] = trim($vArr[0]);
            } else if (strpos($v, '.') !== false) {
                $vArrx = explode('.', $v);
                $rn = trim($vArrx[1]);
                $this->tableSelectAs[$rn] = $v;
            } else {
                $this->tableSelectAs[$v] = $v;
            }
        };



        $tableSelectAs = array_keys($this->tableSelectAs);

        if (!empty($tableSelectAs)) {
            $query->where(function ($query) {
                $this->query = $query;
                $query = self::filterQuery();
            });
        }


        if (!empty($this->search) && !empty($req['search'])) {
            $query->where(function ($query) use ($req) {
                foreach ($this->search as $key => $v) {
                    $val = $this->tableSelectAs[$v];
                    $query->orWhere($val, 'LIKE', "%{$req['search']}%");
                }
            });
        }

        if (!empty($req['sort'])) {
            if (is_array($req['sort']) && !empty($req['sort'])) {
                foreach ($req['sort'] as $k => $v) {
                    if (in_array($k, $tableSelectAs) && in_array(strtolower($v), $defaultData['order_by'])) {
                        $query->orderBy($k, $v);
                    }
                }
            } else {

                if (static::startsWith($req['sort'], '-')) {
                    $columnsSort = substr($req['sort'], 1);
                    if (in_array($columnsSort, $tableSelectAs)) {
                        $query->orderBy($columnsSort, 'desc');
                    }
                } else {
                    $columnsSort = $req['sort'];
                    if (in_array($columnsSort, $tableSelectAs)) {
                        if (in_array(strtolower($req['order_by']), $defaultData['order_by'])) {
                            $query->orderBy($columnsSort, $req['order_by']);
                        } else {
                            $query->orderBy($columnsSort, 'asc');
                        }
                    }
                }
            }
        }




        $paginate = $query->paginate($req['limit'], $this->select);

        $content['items'] = $query->get($this->select);
        $content['pagination'] = $this->queryPaginateGenerate($paginate);
        return $content;
    }

    /**
     * Filter Query
     */
    public function filterQuery()
    {
        $query = $this->query;
        $tableSelectAs = array_keys($this->tableSelectAs);

        // print_r($this->tableSelectAs);die;

        foreach ($this->request as $rfk => $value) {
            if (in_array($rfk, $tableSelectAs)) {
                $field = $this->tableSelectAs[$rfk];
                if (is_array($value)) {
                    foreach ($value as $comparison => $val) {
                        if ($val !== '' && $val !== null) {
                            switch ($comparison) {
                                case 'eq':
                                    if ($this->validateDate($val, 'Y-m-d')) {
                                        $query->whereDate($field, '=', $val);
                                    } else {
                                        $query->where($field, '=', $val);
                                    }
                                    break;

                                case 'neq':
                                    $query->where($field, '!=', $val);
                                    break;

                                case 'lt':
                                    if ($this->validateDate($val, 'Y-m-d')) {
                                        $query->whereDate($field, '<', $val);
                                    } else {
                                        $query->where($field, '<', $val);
                                    }
                                    break;

                                case 'gt':
                                    if ($this->validateDate($val, 'Y-m-d')) {
                                        $query->whereDate($field, '>', $val);
                                    } else {
                                        $query->where($field, '>', $val);
                                    }
                                    break;

                                case 'lte':
                                    if ($this->validateDate($val, 'Y-m-d')) {
                                        $query->whereDate($field, '<=', $val);
                                    } else {
                                        $query->where($field, '<=', $val);
                                    }
                                    break;

                                case 'gte':
                                    if ($this->validateDate($val, 'Y-m-d')) {
                                        $query->whereDate($field, '>=', $val);
                                    } else {
                                        $query->where($field, '>=', $val);
                                    }
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

                                case 'btw':
                                    $val = !is_array($val) ? explode(',', $val) : $val;
                                    if (count($val) == 2) $query->whereBetween($field, $val);
                                    break;

                                case 'nbtw':
                                    $val = !is_array($val) ? explode(',', $val) : $val;
                                    if (count($val) == 2) $query->whereBetween($field, $val);
                                    break;

                                case 'isn':
                                    $val = !is_array($val) ? explode(',', $val) : $val;
                                    $query->whereNull($field, $val);
                                    break;

                                case 'nisn':
                                    $val = !is_array($val) ? explode(',', $val) : $val;
                                    $query->whereNotNull($field, $val);
                                    break;
                            }
                        }
                    }
                } else {
                    if ($value !== '' && $value !== null) {
                        if ($this->validateDate($value, 'Y-m-d')) {
                            $query->whereDate($field, '=', $value);
                        } else {
                            $query->where($field, '=', $value);
                        }
                    }
                }
            }
        }
        return $query;
    }

    public function validateDate($date, $format = 'Y-m-d H:i:s')
    {
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }
}
