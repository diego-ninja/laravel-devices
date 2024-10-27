# Two-Factor Authentication (2FA) API

## Endpoints Overview

- `GET /api/2fa/code` - Get 2FA QR code
- `POST /api/2fa/verify` - Verify 2FA code
- `PATCH /api/2fa/enable` - Enable 2FA
- `PATCH /api/2fa/disable` - Disable 2FA

## Endpoint Details

### Get 2FA QR Code

Retrieve the QR code for setting up 2FA in an authenticator app.

```http
GET /api/2fa/code
```

#### cURL Example
```bash
curl -X GET "http://your-app.com/api/2fa/code" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

#### Axios Example
```javascript
const response = await axios.get('/api/2fa/code', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

#### Guzzle Example
```php
$response = $client->get('/api/2fa/code');
$qrCode = json_decode($response->getBody(), true);
```

#### Success Response (200)
```json
{
    "code": "data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAA..."
}
```

#### Error Response (400)
```json
{
    "message": "Two factor authentication is not enabled for current user"
}
```

### Verify 2FA Code

Verify a 2FA code provided by the user. Success will unlock the current session and verify the device.

```http
POST /api/2fa/verify
```

#### Request Body
```json
{
    "code": "123456"
}
```

#### cURL Example
```bash
curl -X POST "http://your-app.com/api/2fa/verify" \
  -H "Authorization: Bearer your-token" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"code":"123456"}'
```

#### Axios Example
```javascript
const response = await axios.post('/api/2fa/verify', {
    code: '123456'
}, {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json',
        'Content-Type': 'application/json'
    }
});
```

#### Guzzle Example
```php
$response = $client->post('/api/2fa/verify', [
    'json' => ['code' => '123456']
]);
```

#### Success Response (200)
```json
{
    "message": "Two factor authentication successful"
}
```

#### Error Response (400)
```json
{
    "message": "Two factor authentication failed"
}
```

### Enable 2FA

Enable two-factor authentication for the current user.

```http
PATCH /api/2fa/enable
```

#### cURL Example
```bash
curl -X PATCH "http://your-app.com/api/2fa/enable" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

#### Axios Example
```javascript
const response = await axios.patch('/api/2fa/enable', {}, {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

#### Guzzle Example
```php
$response = $client->patch('/api/2fa/enable');
```

#### Success Response (200)
```json
{
    "message": "Two factor authentication enabled for current user"
}
```

#### Error Response (400)
```json
{
    "message": "Two factor authentication already enabled for current user"
}
```

### Disable 2FA

Disable two-factor authentication for the current user.

```http
PATCH /api/2fa/disable
```

#### cURL Example
```bash
curl -X PATCH "http://your-app.com/api/2fa/disable" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

#### Axios Example
```javascript
const response = await axios.patch('/api/2fa/disable', {}, {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

#### Guzzle Example
```php
$response = $client->patch('/api/2fa/disable');
```

#### Success Response (200)
```json
{
    "message": "Two factor authentication disabled for current user"
}
```

#### Error Response (400)
```json
{
    "message": "Two factor authentication is not enabled for current user"
}
```

## Complete Implementation Examples

### Frontend Implementation

```javascript
// 2FA Setup Component
const TwoFactorSetup = {
    data() {
        return {
            qrCode: null,
            verificationCode: '',
            isEnabled: false,
            error: null
        }
    },
    
    methods: {
        async enable2FA() {
            try {
                // Enable 2FA
                await axios.patch('/api/2fa/enable');
                
                // Get QR code
                const response = await axios.get('/api/2fa/code');
                this.qrCode = response.data.code;
                this.isEnabled = true;
            } catch (error) {
                this.error = error.response.data.message;
            }
        },
        
        async verify2FA() {
            try {
                await axios.post('/api/2fa/verify', {
                    code: this.verificationCode
                });
                
                // Redirect on success
                window.location.href = '/dashboard';
            } catch (error) {
                this.error = error.response.data.message;
            }
        },
        
        async disable2FA() {
            try {
                await axios.patch('/api/2fa/disable');
                this.isEnabled = false;
                this.qrCode = null;
            } catch (error) {
                this.error = error.response.data.message;
            }
        }
    }
}
```

### Backend Implementation

```php
// Example 2FA Controller Implementation
class TwoFactorController extends Controller
{
    public function setup(Request $request)
    {
        $user = $request->user();
        
        if ($user->google2faEnabled()) {
            return response()->json([
                'message' => 'Two factor authentication already enabled'
            ], 400);
        }
        
        try {
            // Enable 2FA with new secret
            $user->enable2fa(
                app(Google2FA::class)->generateSecretKey()
            );
            
            // Get QR code for setup
            $qrCode = $user->google2faQrCode();
            
            return response()->json([
                'code' => $qrCode
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to enable two factor authentication'
            ], 500);
        }
    }
    
    public function verify(Request $request)
    {
        $user = $request->user();
        $code = $request->input('code');
        
        try {
            $valid = app(Google2FA::class)->verifyKeyNewer(
                $user->google2fa->secret(),
                $code,
                $user->google2fa->last_success_at?->timestamp ?? 0
            );
            
            if ($valid !== false) {
                $user->google2fa->success();
                event(new Google2FASuccess($user));
                
                return response()->json([
                    'message' => 'Two factor authentication successful'
                ]);
            }
        } catch (\Exception $e) {
            report($e);
        }
        
        event(new Google2FAFailed($user));
        return response()->json([
            'message' => 'Two factor authentication failed'
        ], 400);
    }
}
```

## Rate Limiting

The 2FA verification endpoint includes rate limiting to prevent brute force attacks:

```php
// Example rate limiting implementation
'2fa_verify' => [
    'attempts' => 5,
    'decay_minutes' => 5
]
```

After 5 failed attempts, the user must wait 5 minutes before trying again:

```json
{
    "message": "Too Many Attempts.",
    "retry_after": 300
}
```

## Events

The 2FA system dispatches the following events:

### Google2FASuccess
```php
event(new Google2FASuccess($user));
```

### Google2FAFailed
```php
event(new Google2FAFailed($user));
```

## Security Considerations

1. **Transport Security**
    - Always use HTTPS for 2FA endpoints
    - Never log or expose 2FA secrets
    - Store secrets encrypted at rest

2. **Validation**
    - Implement proper rate limiting
    - Validate code format before verification
    - Use appropriate time windows for code validation

3. **Recovery**
    - Implement backup codes or recovery process
    - Document recovery procedures for users
    - Keep audit logs of 2FA activities

## Next Steps

- Review [Session Management API](sessions.md)
- Learn about [Device Management API](devices.md)
- Explore [Events System](../events.md)