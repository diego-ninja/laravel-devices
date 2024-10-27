# Session Management API

## Endpoints Overview

- `GET /api/sessions` - List all user sessions
- `GET /api/sessions/active` - List active sessions
- `GET /api/sessions/{uuid}` - Get session details
- `PATCH /api/sessions/{uuid}/renew` - Renew session
- `DELETE /api/sessions/{uuid}/end` - End session
- `PATCH /api/sessions/{uuid}/block` - Block session
- `PATCH /api/sessions/{uuid}/unblock` - Unblock session
- `POST /api/sessions/signout` - Sign out from all sessions

## Endpoint Details

### List All Sessions

Retrieve all sessions for the authenticated user.

```http
GET /api/sessions
```

#### cURL Example
```bash
curl -X GET "http://your-app.com/api/sessions" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

#### Axios Example
```javascript
const response = await axios.get('/api/sessions', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

#### Guzzle Example
```php
$response = $client->get('/api/sessions');
$sessions = json_decode($response->getBody(), true);
```

#### Success Response (200)
```json
{
    "data": [
        {
            "uuid": "98765432-fedc-ba98-7654-321fedcba987",
            "ip": "192.168.1.1",
            "location": {
                "ip": "192.168.1.1",
                "hostname": "host.example.com",
                "country": "ES",
                "region": "Madrid",
                "city": "Madrid",
                "postal": "28001",
                "latitude": "40.4168",
                "longitude": "-3.7038",
                "timezone": "Europe/Madrid",
                "label": "28001 Madrid, Madrid, ES"
            },
            "status": "active",
            "last_activity_at": "2024-10-27T15:45:00Z",
            "started_at": "2024-10-27T14:30:00Z",
            "finished_at": null,
            "device": {
                "uuid": "01234567-89ab-cdef-0123-456789abcdef",
                "status": "verified",
                "browser": {
                    "name": "Chrome",
                    "version": {
                        "major": "118",
                        "minor": "0",
                        "patch": "0"
                    }
                }
            }
        }
    ]
}
```

### List Active Sessions

Retrieve only active sessions for the authenticated user.

```http
GET /api/sessions/active
```

#### cURL Example
```bash
curl -X GET "http://your-app.com/api/sessions/active" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

#### Axios Example
```javascript
const response = await axios.get('/api/sessions/active', {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

#### Guzzle Example
```php
$response = $client->get('/api/sessions/active');
```

#### Success Response (200)
```json
{
    "data": [
        {
            "uuid": "98765432-fedc-ba98-7654-321fedcba987",
            "ip": "192.168.1.1",
            "location": {
                "ip": "192.168.1.1",
                "hostname": "host.example.com",
                "country": "ES",
                "region": "Madrid",
                "city": "Madrid",
                "postal": "28001",
                "latitude": "40.4168",
                "longitude": "-3.7038",
                "timezone": "Europe/Madrid",
                "label": "28001 Madrid, Madrid, ES"
            },
            "status": "active",
            "last_activity_at": "2024-10-27T15:45:00Z",
            "started_at": "2024-10-27T14:30:00Z",
            "finished_at": null,
            "device": {
                "uuid": "01234567-89ab-cdef-0123-456789abcdef",
                "status": "verified"
            }
        }
    ]
}
```

### Get Session Details

Retrieve detailed information about a specific session.

```http
GET /api/sessions/{uuid}
```

#### cURL Example
```bash
curl -X GET "http://your-app.com/api/sessions/98765432-fedc-ba98-7654-321fedcba987" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

#### Axios Example
```javascript
const response = await axios.get(`/api/sessions/${sessionUuid}`, {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

#### Guzzle Example
```php
$response = $client->get("/api/sessions/{$sessionUuid}");
```

#### Success Response (200)
```json
{
    "data": {
        "uuid": "98765432-fedc-ba98-7654-321fedcba987",
        "ip": "192.168.1.1",
        "location": {
            "ip": "192.168.1.1",
            "hostname": "host.example.com",
            "country": "ES",
            "region": "Madrid",
            "city": "Madrid",
            "postal": "28001",
            "latitude": "40.4168",
            "longitude": "-3.7038",
            "timezone": "Europe/Madrid",
            "label": "28001 Madrid, Madrid, ES"
        },
        "status": "active",
        "last_activity_at": "2024-10-27T15:45:00Z",
        "started_at": "2024-10-27T14:30:00Z",
        "finished_at": null,
        "device": {
            "uuid": "01234567-89ab-cdef-0123-456789abcdef",
            "status": "verified",
            "browser": {
                "name": "Chrome",
                "version": {
                    "major": "118",
                    "minor": "0",
                    "patch": "0"
                }
            }
        },
        "metadata": {}
    }
}
```

### Renew Session

Update the last activity timestamp of a session.

```http
PATCH /api/sessions/{uuid}/renew
```

#### cURL Example
```bash
curl -X PATCH "http://your-app.com/api/sessions/98765432-fedc-ba98-7654-321fedcba987/renew" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

#### Axios Example
```javascript
const response = await axios.patch(`/api/sessions/${sessionUuid}/renew`, {}, {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

#### Success Response (200)
```json
{
    "message": "Session renewed successfully"
}
```

### Block/Unblock Session

Control session access through blocking/unblocking.

```http
PATCH /api/sessions/{uuid}/block
PATCH /api/sessions/{uuid}/unblock
```

#### cURL Examples
```bash
# Block session
curl -X PATCH "http://your-app.com/api/sessions/98765432-fedc-ba98-7654-321fedcba987/block" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"

# Unblock session
curl -X PATCH "http://your-app.com/api/sessions/98765432-fedc-ba98-7654-321fedcba987/unblock" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

#### Axios Examples
```javascript
// Block session
const blockResponse = await axios.patch(`/api/sessions/${sessionUuid}/block`, {}, {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});

// Unblock session
const unblockResponse = await axios.patch(`/api/sessions/${sessionUuid}/unblock`, {}, {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

#### Success Responses (200)
```json
// Block
{
    "message": "Session blocked successfully"
}

// Unblock
{
    "message": "Session unblocked successfully"
}
```

### End Session

Terminate a specific session.

```http
DELETE /api/sessions/{uuid}/end
```

#### cURL Example
```bash
curl -X DELETE "http://your-app.com/api/sessions/98765432-fedc-ba98-7654-321fedcba987/end" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

#### Axios Example
```javascript
const response = await axios.delete(`/api/sessions/${sessionUuid}/end`, {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

#### Success Response (200)
```json
{
    "message": "Session ended successfully"
}
```

### Sign Out from All Sessions

End all active sessions for the current user.

```http
POST /api/sessions/signout
```

#### cURL Example
```bash
curl -X POST "http://your-app.com/api/sessions/signout" \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

#### Axios Example
```javascript
const response = await axios.post('/api/sessions/signout', {}, {
    headers: {
        'Authorization': `Bearer ${token}`,
        'Accept': 'application/json'
    }
});
```

#### Success Response (200)
```json
{
    "message": "Signout successful"
}
```

## Error Handling

All endpoints may return these common errors:

### Session Locked (423)
```json
{
    "message": "Session locked"
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

### Session Security Management Flow

```javascript
async function manageSessionSecurity(sessionUuid) {
    try {
        // Get session details
        const sessionResponse = await axios.get(`/api/sessions/${sessionUuid}`);
        const session = sessionResponse.data.data;
        
        // Check location
        if (isLocationSuspicious(session.location)) {
            // Block session
            await axios.patch(`/api/sessions/${sessionUuid}/block`);
            return;
        }
        
        // Check inactivity
        const inactivityThreshold = 30 * 60 * 1000; // 30 minutes
        const lastActivity = new Date(session.last_activity_at);
        if (Date.now() - lastActivity > inactivityThreshold) {
            // End session
            await axios.delete(`/api/sessions/${sessionUuid}/end`);
            return;
        }
        
        // Renew active session
        await axios.patch(`/api/sessions/${sessionUuid}/renew`);
        
    } catch (error) {
        handleApiError(error);
    }
}
```

## Next Steps

- Learn about [Device Management API](devices.md)
- Explore [2FA API](2fa.md)
- Review [Events System](../events.md)