<?php

namespace app\components\swagger;

use OpenApi\Annotations as OA;
use OpenApi\Analysis;

/**
 * Процессор для добавления стандартных кодов ответов на ошибки в OpenAPI документацию
 * Добавляет 400, 401, 403, 422, 500 если они не указаны явно для операций
 */
class AddStandardErrorResponsesProcessor
{
    /**
     * Стандартные коды ошибок и их описания
     */
    private static array $standardErrorCodes = [
        400 => 'Bad Request - Неверные параметры запроса',
        401 => 'Unauthorized - Требуется аутентификация',
        403 => 'Forbidden - Доступ запрещен',
        422 => 'Unprocessable Entity - Невозможно обработать запрос',
        500 => 'Internal Server Error - Внутренняя ошибка сервера',
    ];

    public function __invoke(Analysis $analysis): void
    {
        foreach ($analysis->annotations as $annotation) {
            if ($annotation instanceof OA\Operation) {
                $this->addStandardErrorResponses($annotation);
            }
        }
    }

    /**
     * Добавляет стандартные коды ошибок к операции, если они не указаны
     * @param OA\Operation $operation
     */
    private function addStandardErrorResponses(OA\Operation $operation): void
    {
        if (!is_array($operation->responses)) {
            $operation->responses = [];
        }

        $existingCodes = [];
        foreach ($operation->responses as $response) {
            if ($response instanceof OA\Response && is_numeric($response->response)) {
                $existingCodes[] = (int)$response->response;
            }
        }

        foreach (self::$standardErrorCodes as $code => $description) {
            if (!in_array($code, $existingCodes)) {
                $errorResponse = new OA\Response([
                    'response' => $code,
                    'description' => $description,
                    '_context' => $operation->_context,
                ]);
                $operation->responses[] = $errorResponse;
            }
        }
    }
}