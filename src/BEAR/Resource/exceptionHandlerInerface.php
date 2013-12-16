<?php
/**
 * This file is part of the BEAR.Resource package
 *
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace BEAR\Resource;

/**
 * Resource request exception handler
 */
interface exceptionHandlerInterface
{
    public function handle(\Exception $e);
}
