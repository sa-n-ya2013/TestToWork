<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends Controller
{
    const MESSAGE_SUCCESS = 'Success';
    const MESSAGE_FAIL = 'Fail';
    const MESSAGE_BAD_REQUEST = 'Bad request';

    /**
     * Отправка сообщения "Успешно"
     *
     * @return Response
     */
    public function sendOK()
    {
        return response()->json(['message' => self::MESSAGE_SUCCESS]);
    }

    /**
     * Отправка сообщения "Ошибка"
     *
     * @return Response
     */
    public function sendFail()
    {
        return response()->json(['message' => self::MESSAGE_FAIL]);
    }

    /**
     * Отправка сообщения "Плохой запрос"
     *
     * @return Response
     */
    public function sendBadRequest()
    {
        return response()->json(['message' => self::MESSAGE_BAD_REQUEST], Response::HTTP_BAD_REQUEST);
    }
}