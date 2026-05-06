<?php


namespace App\Types\Api;


use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class ApiResponseType
{


    public  bool $success;

    public string $message = '';

    public mixed $data = null;
    protected static function getValidationRules(): array
    {
        return [
            'success' => 'required|boolean',
            'message' => 'required|string',
            'data' => 'nullable',
        ];
    }



    public static function toArray(bool $success , string $message , mixed $data = null): array
    {
        return [
            'success' => $success,
            'message' => $message,
            'data' => $data,
        ];
    }

    protected static function validate(bool $success = false, string $message = '', mixed $data = null): bool
    {
        $rules = self::getValidationRules();
        $validator = Validator::make(data: self::toArray(success: $success,message:  $message,data: $data), rules: $rules);
        if ($validator->fails()) {
            throw new InvalidArgumentException(message: 'Validation failed: ' . implode(', ', $validator->errors()->all()));
        }

        return true;
    }

    public static function sendJsonResponse(bool $success , string $message, mixed $data = null, int $status = 200, $isValidationError = false): JsonResponse
    {

        $message = __($message);
        try{
            self::validate(success: $success,message:  $message,data: $data);
        } catch (InvalidArgumentException $e) {
            $success = false;
            $message = $e->getMessage();
            $data = null;
        }


        return response()->json(self::toArray(success: $success,message:  $message,data: $data), $status);
    }

    public static function responseFromPaginator(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'data' => $paginator->items(),
        ];
    }

}
