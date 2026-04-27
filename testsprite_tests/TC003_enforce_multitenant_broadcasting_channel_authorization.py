import requests
from requests.auth import HTTPBasicAuth

BASE_URL = "http://localhost:80/vivensi-laravel/public"
AUTH_ENDPOINT = "/broadcasting/auth"
TIMEOUT = 30
USERNAME = "nei@vivensi.app.br"
PASSWORD = "12345678"

def enforce_multitenant_broadcasting_channel_authorization():
    auth = HTTPBasicAuth(USERNAME, PASSWORD)
    headers = {
        "Content-Type": "application/json"
    }

    # Channel that belongs to authenticated tenant (allowed)
    allowed_channel = "private-tenant.1.whatsapp"
    # Channel that belongs to different tenant (forbidden)
    forbidden_channel = "private-tenant.999.whatsapp"

    # Test allowed channel authorization
    payload_allowed = {
        "channel_name": allowed_channel,
        "socket_id": "1234.5678"
    }
    try:
        resp_allowed = requests.post(
            BASE_URL + AUTH_ENDPOINT,
            auth=auth,
            headers=headers,
            json=payload_allowed,
            timeout=TIMEOUT
        )
    except requests.RequestException as e:
        assert False, f"Request to allowed channel failed with exception: {e}"

    assert resp_allowed.status_code == 200, (
        f"Expected 200 OK for allowed tenant channel authorization, got {resp_allowed.status_code}: {resp_allowed.text}"
    )

    # Test forbidden channel authorization (cross-tenant)
    payload_forbidden = {
        "channel_name": forbidden_channel,
        "socket_id": "1234.5678"
    }
    try:
        resp_forbidden = requests.post(
            BASE_URL + AUTH_ENDPOINT,
            auth=auth,
            headers=headers,
            json=payload_forbidden,
            timeout=TIMEOUT
        )
    except requests.RequestException as e:
        assert False, f"Request to forbidden channel failed with exception: {e}"

    assert resp_forbidden.status_code == 403, (
        f"Expected 403 Forbidden for cross-tenant channel authorization, got {resp_forbidden.status_code}: {resp_forbidden.text}"
    )

enforce_multitenant_broadcasting_channel_authorization()