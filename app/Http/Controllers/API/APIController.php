<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

/**
 * abstract class for all API controllers
 *
 * This class provide functions to generate the standard response
 *
 * @package App\Http\Controllers
 */
abstract class APIController extends Controller
{
    use AuthorizesRequests;
    use DispatchesJobs;
    use ValidatesRequests;


    /**
     * Get the current authed user
     *
     * @return Authenticatable|null
     */
    public function getAuthedUser(): User|null
    {
        return Auth::guard('api')->user();
    }

    /**
     * Get a valid page number
     *
     * @return int
     */
    public function getPage(): int
    {
        $page = request()->input('page', 1);
        if($page < 1) {
            $page = 1;
        }
        return $page;
    }

    /**
     * Get a valid page size
     *
     * @return int
     */
    public function getPageSize(): int
    {
        $pageSize = request()->input('page_size', config('app.page_size', 10));
        if($pageSize > config('app.page_size_max', 100)) {
            $pageSize = config('app.page_size_max', 100);
        }

        if($pageSize < 5) {
            $pageSize = 5;
        }
        return $pageSize;
    }

    /**
     * Generates a 201 json encoded status message
     *
     * Only use this function when the API call is create some resource
     *
     * if any data need to be returned, pass it as the second parameter
     *
     * data must be an array
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function respondCreated(string $message = 'The resource has been created', array $data = null)
    {
        $payload = [
            'meta'  =>  [
                'code'  =>  201,
                'message'   =>  $message,
            ],
        ];

        if (!empty($data)) {
            $payload['data'] = $data;
        }
        return $this->respond($payload, 201);
    }

    /**
     * Generates a 202 json encoded status message
     *
     * Only use this function when the request is accepted but not processed yet
     * e.g. trigger a broadcast event
     *
     * if any data need to be returned, pass it as the second parameter
     * data must be an array
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function respondAccepted(string $message = 'The resource has been accepted', array $data = null)
    {
        $payload = [
            'meta'  =>  [
                'code'  =>  202,
                'message'   =>  $message,
            ],
        ];

        if (!empty($data)) {
            $payload['data'] = $data;
        }

        return $this->respond($payload, 202);
    }

    /**
     * Generates a 204 json encoded status message
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function respondUnauthorized(string $message = 'Unauthorized')
    {
        return $this->respondWithError([
            [
                'code'  =>  401,
                'message' => $message,
            ],
        ], 401, $message);
    }

    /**
     * Generate a standard response for a deleted resource
     *
     * if any data need to be returned, pass it as the second parameter
     * data must be an array
     *
     * @param string $message
     * @param array|null $data
     * @return JsonResponse
     */
    protected function respondDeleted(string $message = 'The resource has been deleted', array $data = null)
    {
        $payload = [
            'meta'  =>  [
                'code'  =>  200,
                'message'   =>  $message,
            ],
        ];

        if (!is_null($data)) {
            $payload['data'] = $data;
        }

        return $this->respond($payload, 200);
    }

    /**
     * Generates a 400 json encoded error message
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function respondBadRequest(string $message = 'Bad request!')
    {
        return $this->respondWithError([
            [
                'code'  =>  400,
                'message' => $message,
            ],
        ], 400, $message);
    }

    /**
     * Validation wrapper
     *
     * @param array $errors
     * @param string|null $message
     * @return JsonResponse
     */
    protected function respondValidationErrors(array $errors, string $message = null)
    {
        return $this->respond([
            'meta'  =>  [
                'code'  =>  422,
                'message'   =>  is_null($message) ? $errors[array_keys($errors)[0]] ?? 'Validation error' : $message,
            ],
            'errors'    =>  $errors,
        ], 422);
    }

    protected function respondUnprocessableEntity(string $message = 'Unprocessable entity')
    {
        return $this->respondWithError([
            [
                'code'  =>  422,
                'message' => $message,
            ],
        ], 422, $message);
    }


    /**
     * Generates a 403 json encoded error message
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function respondForbidden(string $message = 'Forbidden!')
    {
        return $this->respondWithError([
            [
                'code'  =>  403,
                'message' => $message,
            ],
        ], 403, $message);
    }

    /**
     * Generates a 404 json encoded error message
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function respondNotFound(string $message = 'Not found!')
    {

        return $this->respondWithError([
            [
                'code'  =>  404,
                'message' => $message,
            ],
        ], 404, $message);
    }


    /**
     * Generate a standard response representing an request has been processed successfully.
     *
     * if any data need to be returned, pass it as the second parameter
     * data must be an array
     *
     * @param string $message
     * @param array|null $data
     * @return JsonResponse
     */
    protected function respondOk(string $message = 'OK', array $data = null)
    {
        return $this->respondWithWrapper($data, $message, 200);
    }



    /**
     * Generates a json response
     *
     * @param array $data
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function respond(array $data, int $statusCode = 200, array $headers = [])
    {
        return response()->json($data, $statusCode, $headers);
    }

    /**
     * Creates an array with and error messages and a status code
     *
     * @param array $errors
     * @param int $statusCode
     * @return JsonResponse
     */
    protected function respondWithError(array $errors, int $statusCode = 200, string $message = '')
    {
        $responseArray = [
            'meta'  =>  [
                'code'  =>  $statusCode,
                'message'   =>  $message ?? "There's an error, please read the errors key for more details",
            ],
            'errors' => $errors,
        ];

        return $this->respond($responseArray, $statusCode);
    }

    /**
     * Generates a 500 json encoded error message
     *
     * @param string $message
     * @return JsonResponse
     */
    protected function respondServerError(string $message = 'Server error')
    {
        return $this->respondWithError([
            [
                'code'  =>  500,
                'message' => $message,
            ],
        ], 500, $message);
    }

    /**
     * Build an API response wrapper array
     * put the data with the $wrapper['data'] key then return the wrapper as a json response
     *
     * @param int $code
     * @param string $message
     * @return array
     */
    protected function buildRespondDataWrapper(int $code = 200, string $message = 'ok')
    {
        return [
            'meta'  =>  [
                'code'  =>  $code,
                'message'   =>  $message,
            ],
        ];
    }

    /**
     * Respond with a wrapper and data
     *
     * @param array|null $data
     * @param string $message
     * @param int $statusCode
     * @param array $headers
     * @return JsonResponse
     */
    protected function respondWithWrapper(array|null $data, string $message = '', int $statusCode = 200, array $headers = [])
    {
        $wrapper = $this->buildRespondDataWrapper($statusCode, $message);
        if (isset($data)) {
            $wrapper['data'] = $data;
        }

        return $this->respond($wrapper, $statusCode, $headers);
    }
}
