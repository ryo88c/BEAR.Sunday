<?php
/**
 * This file is part of the BEAR.Framework package
 *
 * @package BEAR.Framework
 * @license http://opensource.org/licenses/bsd-license.php BSD
 */
namespace BEAR\Framework\Exception;

use BEAR\Resource\Exception\BadRequest;
use BEAR\Resource\Exception\MethodNotAllowed;
use BEAR\Resource\Exception\InvalidParameter;
use BEAR\Resource\Exception\InvalidScheme;
use Ray\Di\Exception\InvalidBinding;
use BEAR\Framework\Resource\Page\Error;
use BEAR\Framework\Exception\ResourceNotFound;
use BEAR\Framework\Web\ResponseInterface;
use BEAR\Framework\Inject\LogDirInject;
use Exception;
use Ray\Di\Di\Inject;
use Ray\Di\Di\Named;

/**
 * Exception handler
 *
 * @package BEAR.Framework
 */
class ExceptionHandler implements ExceptionHandlerInterface
{
    use LogDirInject;

    /**
     * Set response
     *
     * @param ResponseInterface $response
     */
    public function setResponse(ResponseInterface $response)
    {
        $this->response = $response;
    }

    /**
     * (non-PHPdoc)
     * @see BEAR\Framework\Exception.ExceptionHandlerInterface::handle()
     */
    public function handle(Exception $e)
    {
        $mode = isset($_ENV['BEAR_OUTPUT_MODE']) ? $_ENV['BEAR_OUTPUT_MODE'] : 'prod';
        $exceptionId = 'e' . substr(md5((string) $e), 0, 5);
        try {
            $response = new Error;
            throw $e;
        } catch (ResourceNotFound $e) {
            $response->code = 404;
            $response->view = 'The requested URI was not found on this service.';
            goto NOT_FOUND;
        } catch (BadRequest $e) {
            $response->code = 400;
            $response->view = 'You sent a request that this service cound not understand.';
            goto METHOD_NOT_ALLOWED;
        } catch (InvalidParameter $e) {
            $response->code = 400;
            $response->view = 'You sent a request that query is not valid.';
            goto BAD_REQUEST;
        } catch (InvalidScheme $e) {
            $response->code = 400;
            $response->view = 'You sent a request that scheme is not valid.';
            goto BAD_REQUEST;
        } catch (MethodNotAllowed $e) {
            $response->code = 405;
            $response->view = 'The requested method is not allowed for this URI.';
            goto METHOD_NOT_ALLOWED;
        } catch (InvalidBinding $e) {
            goto INVALID_BINDING;
        } catch (InvalidUri $e) {
            $response->code = 400;
            goto INVALID_URI;
        } catch (\Exception $e) {
            goto SERVER_ERROR;
        }

        INVALID_BINDING:
        SERVER_ERROR:
        $response->code = 500;

        NOT_FOUND:
        BAD_REQUEST:
        METHOD_NOT_ALLOWED:
        INVALID_URI:

        if (PHP_SAPI === 'cli') {
            $response->view = "Internal error occurred ({$exceptionId})";
        } else {
            // exception screen in develop
            $response->view = include __DIR__ . "/exception.tpl.php";
        }
        $response->headers['X-EXCEPTION-CLASS'] = get_class($e);
        $response->headers['X-EXCEPTION-MESSAGE'] = str_replace(PHP_EOL, ' ', $e->getMessage());
        $response->headers['X-EXCEPTION-CODE'] = $e->getCode();
        $response->headers['X-EXCEPTION-FILE-LINE'] = $e->getFile() . ':' . $e->getLine();
        $previous = $e->getPrevious() ? (get_class($e->getPrevious()) .': ' . str_replace(PHP_EOL, ' ', $e->getPrevious()->getMessage())) : '-';
        $response->headers['X-EXCEPTION-PREVIOUS'] =  $previous;
        $response->headers['X-EXCEPTION-ID'] = $exceptionId;
        $this->writeExceptionLog($e, $exceptionId);

        return $response;
    }

    /**
     * Write exception log
     *
     * @param Exception $e
     * @param string    $exceptionId
     */
    public function writeExceptionLog(Exception $e, $exceptionId)
    {
        $filename = "e.{$exceptionId}.log";
        $trace = $e->getTrace();
        $data = PHP_EOL . $e->getTraceAsString();
        $previousE = $e->getPrevious();
        if ($previousE) {
            $data .= PHP_EOL . PHP_EOL . '-- Previous Exception --' . PHP_EOL . PHP_EOL;
            $data .= $previousE->getTraceAsString();
        }
        $data .= (string) $e;
        $file = "{$this->logDir}/" . $filename;
        file_put_contents($file, $data);
    }
}
