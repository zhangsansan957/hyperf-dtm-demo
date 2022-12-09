<?php

declare(strict_types=1);
/**
 * This file is part of DTM-PHP.
 *
 * @license  https://github.com/dtm-php/dtm-sample/blob/master/LICENSE
 */
namespace App\Controller;

use DtmClient\Api\ApiInterface;
use DtmClient\Middleware\DtmMiddleware;
use DtmClient\TCC;
use DtmClient\TransContext;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\GetMapping;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\PostMapping;
use DtmClient\Annotation\Barrier;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Throwable;

class TccController extends AbstractController
{
    #[Inject]
    protected TCC $tcc;

    #[Inject]
    protected ApiInterface $api;

    public function successCase()
    {

        try {
            $this->tcc->globalTransaction(function (TCC $tcc) {
                $tcc->callBranch(
                    [
                        'trans_name' => 'trans_A',
                        'code' => 't1',
                        'number' => 2,
                    ],
                    $this->serviceUri . '/tcc/transA/try',
                    $this->serviceUri . '/tcc/transA/confirm',
                    $this->serviceUri . '/tcc/transA/cancel'
                );
                $tcc->callBranch(
                    ['trans_name' => 'trans_B'],
                    $this->serviceUri . '/tcc/transB/try',
                    $this->serviceUri . '/tcc/transB/confirm',
                    $this->serviceUri . '/tcc/transB/cancel'
                );
            });
        } catch (Throwable $e) {
            var_dump($e->getMessage(), $e->getTraceAsString());
        }
        return TransContext::getGid();
    }

    public function queryAllCase()
    {
        $result = $this->api->queryAll(['last_id' => '']);
        var_dump($result);
    }

    public function rollbackCase()
    {
        try {
            $this->tcc->globalTransaction(function (TCC $tcc) {
                $tcc->callBranch(
                    [
                        'trans_name' => 'trans_A',
                        'code' => 't1',
                        'number' => 2
                    ],
                    $this->serviceUri . '/tcc/transA/try',
                    $this->serviceUri . '/tcc/transA/confirm',
                    $this->serviceUri . '/tcc/transA/cancel'
                );

                $tcc->callBranch(
                    ['trans_name' => 'trans_B'],
                    $this->serviceUri . '/tcc/transB/try/fail',
                    $this->serviceUri . '/tcc/transB/confirm',
                    $this->serviceUri . '/tcc/transB/cancel'
                );
            });
        } catch (Throwable $exception) {
            // Do Nothing
        }
    }

    public function transATry(RequestInterface $request, ResponseInterface $response)
    {
        var_dump('A try');
        $number = $request->input('number');
        $code = $request->input('code');
        // 查询库存
        if (!Db::table('goods')->where('code', $code)->where('useful_num', '>=', $number)->exists()) {
            return $response->withStatus(409);
        }
        Db::table('goods')->where('code', $code)->update([
            'useful_num' => Db::raw('useful_num -' . $number)
        ]);

        return [
            'dtm_result' => 'SUCCESS',
        ];
    }

    #[Barrier]
    public function transAConfirm(RequestInterface $request, ResponseInterface $response): array
    {
        var_dump('A confirm');

        return [
            'dtm_result' => 'SUCCESS',
        ];
    }

    #[Barrier]
    public function transACancel(RequestInterface $request, ResponseInterface $response): array
    {
        // var_dump('A cancel');
        // // 减冻结库存
        // try {
        //     Db::table('goods')->where('code', $code)->update([
        //         'useful_num' => Db::raw('useful_num +' . $number),
        //     ]);
        // } catch (\Throwable $e) {
        //     return $response->withStatus(409);
        // }

        return [
            'dtm_result' => 'SUCCESS',
        ];
    }

    public function transBTry(): array
    {
        var_dump('B try');
        return [
            'dtm_result' => 'SUCCESS',
        ];
    }

    #[Barrier]
    public function transBTryFail(ResponseInterface $response)
    {
        return $response->withStatus(409);
    }

    public function transBConfirm(): array
    {
        var_dump('B confirm');
        return [
            'dtm_result' => 'SUCCESS',
        ];
    }

    public function transBCancel(): array
    {
        var_dump('B cancel');
        return [
            'dtm_result' => 'SUCCESS',
        ];
    }
}
