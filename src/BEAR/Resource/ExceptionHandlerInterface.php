<?php
/**
 * This file is part of the BEAR.Resource package
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace BEAR\Resource;

/**
 * Resource request invoker exception handler
 */
interface ExceptionHandlerInterface
{
    /**
     * Resource request invoke handle exception
     *
     * @param \Exception $e
     * @param Request    $request
     *
     * @return resource object or its body
     */
    public function handle(\Exception $e, Request $request);
}
