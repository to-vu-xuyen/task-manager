<?php
namespace common\components\jwt;

use Yii;
use yii\web\UnauthorizedHttpException;
use yii\web\IdentityInterface;
use yii\filters\auth\HttpBearerAuth;

use common\components\jwt\JwtHelper;

class JwtHttpBearerAuth extends HttpBearerAuth
{
    public function authenticate($user, $request, $response)
    {
        $authHeader = $request->getHeaders()->get('Authorization');
        if ($authHeader === null) {
            return null;
        }

        if (!preg_match('/^Bearer\s+(.*?)$/i', $authHeader, $matches)) {
            return null;
        }

        $token = $matches[1];
        if (!JwtHelper::isJwtFormat($token)) {
            return null;
        }
        
        // Verify JWT trước khi gọi loginByAccessToken
        $payload = JwtHelper::decode($token);
        if ($payload === null) {
            // JWT invalid hoặc expired → challenge + fail
            $this->challenge($response);
            $this->handleFailure($response);
            return null; // unreachable, handleFailure throws
        }

        $identity = $user->loginByAccessToken($token, get_class($this));
        if ($identity === null) {
            $this->challenge($response);
            $this->handleFailure($response);
        }
        
        return $identity;
    }
}