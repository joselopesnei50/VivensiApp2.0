import requests
from requests.auth import HTTPBasicAuth

BASE_URL = "http://localhost:80/vivensi-laravel/public"
AUTH = HTTPBasicAuth("nei@vivensi.app.br", "12345678")
TIMEOUT = 30

def test_validate_whatsapp_webhook_payload_sanitization():
    # Payload with script tags to test sanitization
    malicious_payload = {
        "object": "whatsapp_business_account",
        "entry": [
            {
                "id": "1234567890",
                "changes": [
                    {
                        "value": {
                            "messages": [
                                {
                                    "from": "5511999999999",
                                    "id": "wamid.HBgMNzIy...==",
                                    "timestamp": "1609459200",
                                    "text": {
                                        "body": "<script>alert(1)</script>"
                                    },
                                    "type": "text"
                                }
                            ],
                            "contacts": [
                                {
                                    "profile": {
                                        "name": "Malicious User"
                                    },
                                    "wa_id": "5511999999999"
                                }
                            ],
                            "metadata": {
                                "phone_number_id": "1234567890"
                            }
                        },
                        "field": "messages"
                    }
                ]
            }
        ]
    }

    headers = {
        "Content-Type": "application/json"
    }

    try:
        # Send POST request to WhatsApp webhook endpoint with malicious payload
        response = requests.post(
            f"{BASE_URL}/api/whatsapp/webhook",
            json=malicious_payload,
            headers=headers,
            auth=AUTH,
            timeout=TIMEOUT,
        )
    except requests.RequestException as e:
        assert False, f"Request to /api/whatsapp/webhook failed: {e}"

    # The endpoint should accept and sanitize the input, returning 200 OK
    assert response.status_code == 200, f"Expected 200 OK, got {response.status_code}"

    # Since internal transaction creation is async/internal, 
    # verifying the sanitization via GET /transactions is required.
    # Extract transaction ID or verify sanitized data by a follow-up request is not possible from spec,
    # so we will simulate by fetching recent transactions and validating the absence of script tag.
    # Authenticated user needed to GET /transactions.

    try:
        transactions_resp = requests.get(
            f"{BASE_URL}/transactions",
            auth=AUTH,
            timeout=TIMEOUT,
            headers={"Accept": "text/html"}
        )
    except requests.RequestException as e:
        assert False, f"GET /transactions request failed: {e}"

    assert transactions_resp.status_code == 200, f"Expected 200 OK for /transactions, got {transactions_resp.status_code}"
    content = transactions_resp.text

    # Assert that the content does NOT contain the unsanitized script tag
    assert "<script>alert(1)</script>" not in content, "Found unsanitized script tag in transactions content — potential XSS risk"

test_validate_whatsapp_webhook_payload_sanitization()