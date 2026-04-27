import requests
from requests.auth import HTTPBasicAuth

BASE_URL = "http://localhost:80/vivensi-laravel/public"
USERNAME = "nei@vivensi.app.br"
PASSWORD = "12345678"
TIMEOUT = 30

def test_verify_evolution_api_webhook_signature_verification():
    token = "testtoken12345"
    url = f"{BASE_URL}/api/evo/webhook/{token}"

    # Sample payload for POST request
    payload = {
        "event": "test_event",
        "data": {"example": "value"}
    }

    # Compute or specify a valid Webhook-Signature header value
    # Since no signature generation details are given, we simulate a valid signature
    valid_signature = "sha256=validsignaturevalue"

    headers_with_valid_signature = {
        "Webhook-Signature": valid_signature,
        "Content-Type": "application/json"
    }

    headers_missing_signature = {
        "Content-Type": "application/json"
    }

    headers_invalid_signature = {
        "Webhook-Signature": "sha256=invalidsignature",
        "Content-Type": "application/json"
    }

    auth = HTTPBasicAuth(USERNAME, PASSWORD)

    # 1. POST with valid signature header -> Expect 200 OK
    response_valid = None
    try:
        response_valid = requests.post(url, json=payload, headers=headers_with_valid_signature, auth=auth, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Request with valid signature failed: {e}"
    assert response_valid is not None
    assert response_valid.status_code == 200, f"Expected 200 OK for valid signature, got {response_valid.status_code}"

    # 2. POST missing signature header -> Expect 401 Unauthorized
    response_missing = None
    try:
        response_missing = requests.post(url, json=payload, headers=headers_missing_signature, auth=auth, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Request with missing signature failed: {e}"
    assert response_missing is not None
    assert response_missing.status_code == 401, f"Expected 401 Unauthorized for missing signature, got {response_missing.status_code}"

    # 3. POST with invalid signature header -> Expect 401 Unauthorized
    response_invalid = None
    try:
        response_invalid = requests.post(url, json=payload, headers=headers_invalid_signature, auth=auth, timeout=TIMEOUT)
    except requests.RequestException as e:
        assert False, f"Request with invalid signature failed: {e}"
    assert response_invalid is not None
    assert response_invalid.status_code == 401, f"Expected 401 Unauthorized for invalid signature, got {response_invalid.status_code}"

test_verify_evolution_api_webhook_signature_verification()