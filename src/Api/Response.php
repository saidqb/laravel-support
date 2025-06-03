<?php
namespace Saidqb\LaravelSupport\Api;

use Saidqb\LaravelSupport\Api\Paginate;
use Illuminate\Support\Arr;
use Illuminate\Pagination\LengthAwarePaginator;

// larvvel
class Response
{
    public $key_status = 'status';
    public $key_message = 'message';
    public $key_error_code = 'error_code';
    public $key_results = 'results';

    protected $status = 200;
    protected $http_status;
    protected $message = 'Success';
    protected $error_code = '';
    protected $results = [];
    protected $data = null;
    protected $multi = false;
    protected $pagination = true;
    protected $paginationData = null;


    protected $append = [];

    protected $modifyResults;


    public function __construct($data = [])
    {
        $this->data = $data;
    }

    public static function make($data = [])
    {
        return new static($data);
    }

    public function data($data = null)
    {
        $this->data = $data;
        return $this;
    }

    public function append($key, $value = null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->append = Arr::add($this->append, $k, $v);
            }
            return $this;
        }

        if ($value) {
            $this->append = Arr::add($this->append, $key, $value);
            return $this;
        }
        return $this;
    }


    public function status(int $status = 200)
    {
        $this->status = $status;
        return $this;
    }

    public function message(string $message  = 'success')
    {
        $this->message = $message;
        return $this;
    }

    public function errorCode(string $error_code = '')
    {
        $this->error_code = $error_code;
        return $this;
    }

    public function multi(bool $multi = true)
    {
        $this->multi = $multi;
        return $this;
    }

    public function pagination(bool $pagination = true)
    {
        $this->pagination = $pagination;
        return $this;
    }

    public function paginationData($paginationData = null)
    {
        $this->paginationData = $paginationData;
        return $this;
    }

    public function httpStatus($http_status = null)
    {
        $this->http_status = $http_status;
        return $this;
    }

    public function modifyResults($callback)
    {
        $this->modifyResults = $callback;
        return $this;
    }

    public function buildResult()
    {
        $res = [
            $this->key_status => $this->status,
            $this->key_message => $this->message,
            $this->key_error_code => $this->error_code,
        ];

        if ($this->multi) {

            if ($this->data instanceof LengthAwarePaginator) {
                $data = $this->data->items();
                $results['data'] = $data;

                if ($this->pagination) {
                    $paginate = Paginate::make($this->data)->get();

                    $results['pagination'] = $paginate;
                }
            } else {
                $data = $this->data;
                $results['data'] = $data;

                if ($this->pagination) {

                    if ($this->paginationData instanceof LengthAwarePaginator) {
                        $paginate = Paginate::make($this->paginationData)->get();
                        $results['pagination'] = $paginate;
                    } else {
                        $results['pagination'] = $this->paginationData ?? (object) null;
                    }
                }
            }

            $this->results =  $results;
        } else {
            $this->results =  $this->data;
        }

        if (is_callable($this->modifyResults)) {
            $this->results = call_user_func($this->modifyResults, $this->results);
        }

        if (!$this->multi) {
            if (empty($this->results)) {
                $this->results = (object) null;
            }
        }

        $res[$this->key_results] = $this->results;

        if (!$this->http_status) {
            $this->http_status = $this->status;
        }
        $res = array_merge($res, $this->append);

        return $res;
    }


    public function get()
    {
        return response()->json($this->buildResult(), $this->http_status);
    }

    public function getArray()
    {
        return $this->buildResult();
    }

    public function validationError()
    {
        $this->status(422)
            ->message('Validation Error')
            ->errorCode('validation_error')
            ->httpStatus(422);
        return $this;
    }

    public function error()
    {
        $this->status(500)
            ->message('Internal Server Error')
            ->errorCode('internal_server_error')
            ->httpStatus(500);
        return $this;
    }

    public function forbidden()
    {
        $this->status(403)
            ->message('Forbidden Access')
            ->errorCode('forbidden')
            ->httpStatus(403);
        return $this;
    }

    public function unauthorized()
    {
        $this->status(401)
            ->message('Unauthorized Access')
            ->errorCode('unauthorized')
            ->httpStatus(401);
        return $this;
    }

    public function notFound()
    {
        $this->status(404)
            ->message('Not Found')
            ->errorCode('not_found')
            ->httpStatus(200);
        return $this;
    }


}
