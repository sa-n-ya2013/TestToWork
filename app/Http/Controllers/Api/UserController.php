<?php

namespace App\Http\Controllers\Api;

use Auth;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class UserController extends ApiController
{
    const FIELD_LOGIN = 'login';
    const FIELD_EMAIL = 'email';
    const FIELD_PASSWORD = 'password';

    /**
     * Авторизация пользователя
     *
     * @param Request $request
     * @return Response
     */
    public function auth(Request $request)
    {
        $login = $request->input(self::FIELD_LOGIN, '');
        $email = $request->input(self::FIELD_EMAIL, '');
        $password = $request->input(self::FIELD_PASSWORD, '');

        if (!$login && !$email || !$password) {
            return $this->sendBadRequest();
        }

        if ($email && Auth::attempt(['email' => $email, 'password' => $password])) {
            return $this->sendOK();
        }
        if ($login && Auth::attempt(['login' => $login, 'password' => $password])) {
            return $this->sendOK();
        }

        return $this->sendFail();
    }
}