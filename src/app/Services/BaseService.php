<?php

namespace App\Services;

use App\Helpers\LogHelperService;
use Illuminate\Support\Facades\DB;

/**
 * Class BaseService
 */
abstract class BaseService
{
    protected LogHelperService $logger;

    /**
     * BaseService constructor.
     */
    protected function __construct()
    {
        $this->logger = app(LogHelperService::class);
    }

    /**
     * beginTransaction
     *
     * @return void
     */
    protected function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    /**
     * commit
     *
     * @return void
     */
    protected function commit(): void
    {
        DB::commit();
    }

    /**
     * rollBack
     *
     * @return void
     */
    protected function rollBack(): void
    {
        DB::rollBack();
    }
}
