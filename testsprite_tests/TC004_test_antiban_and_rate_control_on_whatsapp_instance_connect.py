import requests
import base64
import time

BASE_URL = "http://localhost:80/vivensi-laravel/public"
AUTH_CREDENTIALS = {"email": "nei@vivensi.app.br", "password": "12345678"}
HEADERS = {"Accept": "application/json"}
TIMEOUT = 30


def test_antiban_and_rate_control_on_whatsapp_instance_connect():
    session = requests.Session()
    session.headers.update(HEADERS)

    # Perform login to obtain session cookie for authentication
    login_url = f"{BASE_URL}/login"
    login_resp = session.post(login_url, data=AUTH_CREDENTIALS, timeout=TIMEOUT, allow_redirects=False)
    assert login_resp.status_code in (302, 200), f"Login failed: {login_resp.status_code}, {login_resp.text}"

    instance_id = None

    def create_instance():
        url = f"{BASE_URL}/api/whatsapp/instances"
        payload = {"name": "test-instance-antiban-ratecontrol"}
        resp = session.post(url, json=payload, timeout=TIMEOUT)
        assert resp.status_code == 201, f"Failed to create instance: {resp.status_code}, {resp.text}"
        return resp.json().get("id")

    def delete_instance(id):
        url = f"{BASE_URL}/api/whatsapp/instances/{id}"
        resp = session.delete(url, timeout=TIMEOUT)
        assert resp.status_code == 200, f"Failed to delete instance: {resp.status_code}, {resp.text}"

    try:
        # Create WhatsApp instance
        instance_id = create_instance()
        assert instance_id is not None, "Instance ID not returned"

        connect_url = f"{BASE_URL}/api/whatsapp/instances/{instance_id}/connect"

        # 1. Test successful connect (200 OK)
        resp = session.post(connect_url, timeout=TIMEOUT)
        assert resp.status_code == 200, f"Initial connect not successful: {resp.status_code}, {resp.text}"
        json_data = resp.json()
        assert "status" in json_data or "qr" in json_data, "Missing connection status or QR in response"

        # 2. Test rate limit: make rapid repeated calls until 429 is received or max attempts reached
        max_calls = 20
        got_429 = False
        for _ in range(max_calls):
            r = session.post(connect_url, timeout=TIMEOUT)
            if r.status_code == 429:
                got_429 = True
                break
            # Expecting either 200 or 429, any other status is error
            assert r.status_code == 200, f"Unexpected status code during rate limit test: {r.status_code}"
            time.sleep(0.1)  # short delay to not flood too fast (though we test rate limit)

        assert got_429, "Rate limit 429 response not received after repeated calls"

        # 3. Test media integrity validation returns 422 on invalid Base64 media
        # Assume media included in JSON payload simulating invalid Base64
        invalid_media_payload = {
            "media": "invalid-base64-string&^%$#@!",
            "action": "connect"
        }
        resp = session.post(connect_url, json=invalid_media_payload, timeout=TIMEOUT)
        assert resp.status_code == 422, f"Expected 422 on invalid Base64 media, got {resp.status_code}, {resp.text}"

    finally:
        if instance_id:
            delete_instance(instance_id)


test_antiban_and_rate_control_on_whatsapp_instance_connect()
