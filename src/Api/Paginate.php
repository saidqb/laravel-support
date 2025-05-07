<?php
namespace Saidqb\LaravelSupport\Api;

class Paginate
{

    public int $limit = 10;
    public $queryData;

    public function __construct($queryData = null)
    {
        $this->queryData = $queryData;
    }

    public static function make($queryData = null)
    {
        return new static($queryData);
    }

    public function limit(int $limit)
    {
        $this->limit = $limit;
    }


    public function get()
    {
        $paginator = $this->queryData;

        $pagination =  [
            'total_data' => $paginator->total(),
            'total_display' => $paginator->count(),
            'current_page' => $paginator->currentPage(),
            'total_page' => $paginator->lastPage(),
            'limit' => $paginator->perPage(),
            'start' => $paginator->firstItem(),
            'end' => $paginator->lastItem(),
        ];

        $url = $paginator->nextPageUrl();
        $pagination['next_page'] = 0;
        if ($url) {
            $url_components = parse_url($url);
            parse_str($url_components['query'], $params);
            $pagination['next_page'] = (int) $params['page'];
        }

        $url = $paginator->previousPageUrl();
        $pagination['prev_page'] = $paginator->lastPage();
        if ($url) {
            $url_components = parse_url($url);
            parse_str($url_components['query'], $params);
            $pagination['prev_page'] = (int) $params['page'];
        }
        return $pagination;
    }
}
