# Device Management API

## Endpoints Overview

- `GET /api/devices` - List user devices
- `GET /api/devices/{uuid}` - Get device details
- `PATCH /api/devices/{uuid}/verify` - Verify device
- `PATCH /api/devices/{uuid}/hijack` - Mark device as hijacked
- `PATCH /api/devices/{uuid}/forget` - Forget device
- `POST /api/devices/signout` - Sign out from all device sessions

## Endpoint Details

### List User Devices

Lists all devices associated with the authenticated user.

```http
GET /api/devices
```

#### cURL Example
```bash
curl -X GET "http://your-app.com/api/devices" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

#### Axios Example
```javascript
const response = await axios.get('/api/devices', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

#### Guzzle Example
```php
$response = $client->get('/api/devices');
$devices = json_decode($response->getBody(), true);
```

#### Success Response (200)
```json
{
    "data": [
        {
            "uuid": "01234567-89ab-cdef-0123-456789abcdef",
            "status": "verified",
            "verified_at": "2024-10-27T14:30:00Z",
            "browser": {
                "name": "Chrome",
                "version": {
                    "major": "118",
                    "minor": "0",
                    "patch": "0",
                    "label": "118.0.0"
                },
                "family": "Chrome",
                "engine": "Blink",
                "type": "browser",
                "label": "Chrome"
            },
            "platform": {
                "name": "Windows",
                "version": {
                    "major": "10",
                    "minor": "0",
                    "patch": "0",
                    "label": "10.0.0"
                },
                "family": "Windows",
                "label": "Windows"
            },
            "device": {
                "family": "Desktop",
                "model": "PC",
                "type": "desktop"
            },
            "is_current": true,
            "source": "Mozilla/5.0...",
            "ip_address": "192.168.1.1",
            "metadata": {}
        }
    ]
}
```
### Get Device Details

Retrieve detailed information about a specific device.

```http
GET /api/devices/{uuid}
```

#### cURL Example
```bash
curl -X GET "http://your-app.com/api/devices/01234567-89ab-cdef-0123-456789abcdef" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

#### Axios Example
```javascript
const response = await axios.get(`/api/devices/${deviceUuid}`, {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

#### Guzzle Example
```php
$response = $client->get("/api/devices/{$deviceUuid}");
$device = json_decode($response->getBody(), true);
```

#### Success Response (200)
```json
{
    "data": {
        "uuid": "01234567-89ab-cdef-0123-456789abcdef",
        "status": "verified",
        "verified_at": "2024-10-27T14:30:00Z",
        "browser": {
            "name": "Chrome",
            "version": {
                "major": "118",
                "minor": "0",
                "patch": "0",
                "label": "118.0.0"
            },
            "family": "Chrome",
            "engine": "Blink",
            "type": "browser",
            "label": "Chrome"
        },
        "platform": {
            "name": "Windows",
            "version": {
                "major": "10",
                "minor": "0",
                "patch": "0",
                "label": "10.0.0"
            },
            "family": "Windows",
            "label": "Windows"
        },
        "device": {
            "family": "Desktop",
            "model": "PC",
            "type": "desktop"
        },
        "is_current": true,
        "source": "Mozilla/5.0...",
        "ip_address": "192.168.1.1",
        "metadata": {},
        "sessions": [
            {
                "uuid": "98765432-fedc-ba98-7654-321fedcba987",
                "status": "active",
                "started_at": "2024-10-27T14:30:00Z",
                "last_activity_at": "2024-10-27T15:45:00Z"
            }
        ]
    }
}
```

#### Error Response (404)
```json
{
    "message": "Device not found"
}
```

### Verify Device

Mark a device as verified, allowing it to create active sessions without requiring 2FA.

```http
PATCH /api/devices/{uuid}/verify
```

#### cURL Example
```bash
curl -X PATCH "http://your-app.com/api/devices/01234567-89ab-cdef-0123-456789abcdef/verify" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

#### Axios Example
```javascript
const response = await axios.patch(`/api/devices/${deviceUuid}/verify`, {}, {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

#### Guzzle Example
```php
$response = $client->patch("/api/devices/{$deviceUuid}/verify");
```

#### Success Response (200)
```json
{
    "message": "Device verified successfully"
}
```

#### Error Response (404)
```json
{
    "message": "Device not found"
}
```

### Mark Device as Hijacked

Flag a device as potentially compromised, blocking all its sessions.

```http
PATCH /api/devices/{uuid}/hijack
```

#### cURL Example
```bash
curl -X PATCH "http://your-app.com/api/devices/01234567-89ab-cdef-0123-456789abcdef/hijack" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

#### Axios Example
```javascript
const response = await axios.patch(`/api/devices/${deviceUuid}/hijack`, {}, {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

#### Guzzle Example
```php
$response = $client->patch("/api/devices/{$deviceUuid}/hijack");
```

#### Success Response (200)
```json
{
    "message": "Device flagged as hijacked"
}
```

### Forget Device

Remove a device and all its associated sessions.

```http
PATCH /api/devices/{uuid}/forget
```

#### cURL Example
```bash
curl -X PATCH "http://your-app.com/api/devices/01234567-89ab-cdef-0123-456789abcdef/forget" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

#### Axios Example
```javascript
const response = await axios.patch(`/api/devices/${deviceUuid}/forget`, {}, {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

#### Guzzle Example
```php
$response = $client->patch("/api/devices/{$deviceUuid}/forget");
```

#### Success Response (200)
```json
{
    "message": "Device forgotten successfully. All active sessions were ended."
}
```

### Sign Out from Device

End all active sessions for the current device.

```http
POST /api/devices/signout
```

#### cURL Example
```bash
curl -X POST "http://your-app.com/api/devices/signout" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

#### Axios Example
```javascript
const response = await axios.post('/api/devices/signout', {}, {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

#### Guzzle Example
```php
$response = $client->post("/api/devices/signout");
```

#### Success Response (200)
```json
{
    "message": "All active sessions for device finished successfully."
}
```

## Error Handling

All endpoints may return these common errors:

### Unauthorized (401)
```json
{
    "message": "Unauthenticated."
}
```

### Forbidden (403)
```json
{
    "message": "This action is unauthorized."
}
```

### Rate Limited (429)
```json
{
    "message": "Too Many Attempts.",
    "retry_after": 60
}
```

## Usage Examples

### Complete Device Management Flow

```javascript
// Example of complete device management using Axios
async function manageDevice(deviceUuid) {
    try {
        // Get device details
        const deviceResponse = await axios.get(`/api/devices/${deviceUuid}`);
        const device = deviceResponse.data.data;
        
        // Verify device if unverified
        if (device.status === 'unverified') {
            await axios.patch(`/api/devices/${deviceUuid}/verify`);
        }
        
        // Check for suspicious activity
        if (isSuspicious(device)) {
            await axios.patch(`/api/devices/${deviceUuid}/hijack`);
            return;
        }
        
        // End all sessions if needed
        await axios.post('/api/devices/signout');
        
    } catch (error) {
        handleApiError(error);
    }
}
```

## Next Steps

- Learn about [Session Management API](sessions.md)
- Explore [2FA API](2fa.md)
- Review [Events System](../events.md)