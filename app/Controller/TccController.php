<?php

declare(strict_types=1);
/**
 * This file is part of DTM-PHP.
 *
 * @license  https://github.com/dtm-php/dtm-sample/blob/master/LICENSE
 */
namespace App\Controller;

use DtmClient\Api\ApiInterface;
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
                    [
                        'trans_name' => 'trans_B',
                        'code' => 't1',
                        'number' => 2,
                    ],
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
            var_dump($exception->getMessage(), $exception->getTraceAsString());
            // Do Nothing
        }
        return TransContext::getGid();
    }

    #[Barrier]
    public function transATry(RequestInterface $request, ResponseInterface $response)
    {
        var_dump('A try');
        $number = $request->input('number');
        $code = $request->input('code');
        // 查询库存
        if (!Db::table('goods')->where('code', $code)->where('useful_num', '>=', $number)->exists()) {
            return $response->json(['msg' => '库存不足'])->withStatus(409);
        }
        // 冻结库存
        Db::table('goods')->where('code', $code)->update([
            'lock_num' => Db::raw('lock_num +' . $number)
        ]);

        return [
            'dtm_result' => 'SUCCESS',
        ];
    }

    public function transAConfirm(RequestInterface $request, ResponseInterface $response): array
    {
        var_dump('A confirm');
        $number = $request->input('number');
        $code = $request->input('code');
        // 扣减库存
        Db::table('goods')->where('code', $code)->update([
            'lock_num' => Db::raw('lock_num -' . $number),
            'useful_num' => Db::raw('useful_num -' . $number)
        ]);
        return [
            'dtm_result' => 'SUCCESS',
        ];
    }

    #[Barrier]
    public function transACancel(RequestInterface $request, ResponseInterface $response): array
    {
        var_dump('A cancel');
        $number = $request->input('number');
        $code = $request->input('code');
        // 恢复冻结库存
        try {
            Db::table('goods')->where('code', $code)->update([
                'lock_num' => Db::raw('lock_num -' . $number),
            ]);
        } catch (\Throwable $e) {
            return $response->withStatus(409);
        }

        return [
            'dtm_result' => 'SUCCESS',
        ];
    }

    #[Barrier]
    public function transBTry(RequestInterface $request, ResponseInterface $response): array
    {
        var_dump('B try');
        $code = $request->input('code');
        // 校验CODE是否存在
        if (!Db::table('other_goods')->where('code', $code)->exists()) {
            return $response->json(['msg' => 'code不存在'])->withStatus(409);
        }
        return [
            'dtm_result' => 'SUCCESS',
        ];
    }

    public function transBTryFail(ResponseInterface $response)
    {
        var_dump('B try failed');

        return $response->withStatus(409);
    }

    public function transBConfirm(RequestInterface $request, ResponseInterface $response): array
    {
        var_dump('B confirm');
        $number = $request->input('number');
        $code = $request->input('code');
        // 转入库存
        Db::table('other_goods')->where('code', $code)->update([
            'useful_num' => Db::raw('useful_num +' . $number)
        ]);
        return [
            'dtm_result' => 'SUCCESS',
        ];
    }

    #[Barrier]
    public function transBCancel(RequestInterface $request, ResponseInterface $response)
    {
        var_dump('B cancel');

        return [
            'dtm_result' => 'SUCCESS',
        ];
    }
}
