# API Reference

## Overview

Laravel Devices provides a comprehensive REST API divided into three main sections:

1. [Device Management API](api/devices.md) - Manage and track devices
2. [Session Management API](api/sessions.md) - Control user sessions
3. [Two-Factor Authentication API](api/2fa.md) - Handle 2FA operations

## Authentication

All API endpoints require authentication. The package uses your configured Laravel auth guard.

```bash
# Example with Bearer token
curl -X GET /api/devices \
  -H "Authorization: Bearer your-token" \
  -H "Accept: application/json"
```

## Common Error Responses

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

## Best Practices

1. Always include appropriate headers:
    - `Accept: application/json`
    - Valid authentication token
    - `Content-Type: application/json` for POST/PATCH requests

2. Handle rate limiting:
    - Check for 429 status codes
    - Respect the `retry_after` header

3. Implement proper error handling:
    - Handle all possible status codes
    - Validate responses
    - Implement retry logic where appropriate

4. Use appropriate HTTP methods:
    - GET for retrieving data
    - POST for creating
    - PATCH for updates
    - DELETE for removal

## Next Steps

- Read detailed [Device API Documentation](api/devices.md)
- Explore [Session API Documentation](api/sessions.md)
- Check [2FA API Documentation](api/2fa.md)
- Review [Events System](events.md)