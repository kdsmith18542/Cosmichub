<?php

namespace App\Core\Http\Responses;

use App\Core\Http\Response;

/**
 * JSON Response class for API responses
 */
class JsonResponse extends Response
{
    /**
     * @var array The response data
     */
    protected $data;

    /**
     * @var int The JSON encoding options
     */
    protected $encodingOptions = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * Create a new JSON response
     *
     * @param mixed $data
     * @param int $status
     * @param array $headers
     */
    public function __construct($data = null, int $status = 200, array $headers = [])
    {
        parent::__construct('', $status, $headers);
        
        $this->data = $data;
        $this->header('Content-Type', 'application/json');
        $this->setContent($this->data);
    }

    /**
     * Set the response content
     *
     * @param mixed $content
     * @return $this
     */
    public function setContent($content): self
    {
        $this->data = $content;
        $this->content = $this->morphToJson($content);
        return $this;
    }

    /**
     * Get the response data
     *
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Set the JSON encoding options
     *
     * @param int $options
     * @return $this
     */
    public function setEncodingOptions(int $options): self
    {
        $this->encodingOptions = $options;
        $this->setContent($this->data);
        return $this;
    }

    /**
     * Get the JSON encoding options
     *
     * @return int
     */
    public function getEncodingOptions(): int
    {
        return $this->encodingOptions;
    }

    /**
     * Convert the data to JSON
     *
     * @param mixed $data
     * @return string
     */
    protected function morphToJson($data): string
    {
        if ($data instanceof \JsonSerializable) {
            return json_encode($data->jsonSerialize(), $this->encodingOptions);
        }

        return json_encode($data, $this->encodingOptions);
    }

    /**
     * Create a success response
     *
     * @param mixed $data
     * @param string $message
     * @param int $status
     * @return static
     */
    public static function success($data = null, string $message = 'Success', int $status = 200): self
    {
        return new static([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $status);
    }

    /**
     * Create an error response
     *
     * @param string $message
     * @param mixed $errors
     * @param int $status
     * @return static
     */
    public static function error(string $message = 'Error', $errors = null, int $status = 400): self
    {
        $response = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $response['errors'] = $errors;
        }

        return new static($response, $status);
    }

    /**
     * Create a validation error response
     *
     * @param array $errors
     * @param string $message
     * @return static
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): self
    {
        return static::error($message, $errors, 422);
    }

    /**
     * Create a not found response
     *
     * @param string $message
     * @return static
     */
    public static function notFound(string $message = 'Resource not found'): self
    {
        return static::error($message, null, 404);
    }

    /**
     * Create an unauthorized response
     *
     * @param string $message
     * @return static
     */
    public static function unauthorized(string $message = 'Unauthorized'): self
    {
        return static::error($message, null, 401);
    }

    /**
     * Create a forbidden response
     *
     * @param string $message
     * @return static
     */
    public static function forbidden(string $message = 'Forbidden'): self
    {
        return static::error($message, null, 403);
    }

    /**
     * Create a server error response
     *
     * @param string $message
     * @return static
     */
    public static function serverError(string $message = 'Internal server error'): self
    {
        return static::error($message, null, 500);
    }
}