<?php

namespace Saidqb\LaravelSupport\Make;

use Saidqb\LaravelSupport\Concerns\HasHelper;


/*
harus menggunakan paginasi laravel
$query->paginate($req['limit'])
 */

class PaginateApi
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



    public $collection;

    public $setPaginationType = 'default';


    static function make()
    {
        return new static();
    }

    public function type($type = 'default')
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

    /* Collection harus response ->paginate() */
    public function collection($collection = [])
    {
        $this->collection = $collection;
        return $this;
    }


    public function get()
    {
        return $this->queryPaginateGenerate($this->collection);
    }
}
