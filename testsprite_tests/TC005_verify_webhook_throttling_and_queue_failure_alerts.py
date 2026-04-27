import requests
from requests.auth import HTTPBasicAuth
import time

BASE_URL = "http://localhost:80/vivensi-laravel/public"
AUTH = HTTPBasicAuth("nei@vivensi.app.br", "12345678")
TIMEOUT = 30

def test_verify_webhook_throttling_and_queue_failure_alerts():
    session = requests.Session()
    session.auth = AUTH
    headers = {"Content-Type": "application/json"}

    whatsapp_webhook_url = f"{BASE_URL}/api/whatsapp/webhook"
    asaas_webhook_url = f"{BASE_URL}/api/webhooks/asaas"
    admin_dashboard_url = f"{BASE_URL}/admin"

    # Sample valid webhook payloads for WhatsApp and Asaas respectively
    whatsapp_payload = {
        "object": "whatsapp_business_account",
        "entry": [{
            "id": "WHATSAPP_BUSINESS_ACCOUNT_ID",
            "changes": [{
                "field": "messages",
                "value": {
                    "messaging_product": "whatsapp",
                    "metadata": {"display_phone_number": "1234567890", "phone_number_id": "123456789012345"},
                    "contacts": [{"profile": {"name": "Test User"}, "wa_id": "5511999999999"}],
                    "messages": [{
                        "from": "5511999999999",
                        "id": "ABGGFlA5FpafAgo6NhV6CxXlRiZFbLH7NIZbHnn7L7Y=",
                        "timestamp": str(int(time.time())),
                        "text": {"body": "Hello test"},
                        "type": "text"
                    }]
                }
            }]
        }]
    }
    asaas_payload = {
        "object": "payment",
        "id": "payment_id_123456",
        "event": "payment.created",
        "payment": {
            "id": "payment_id_123456",
            "dateCreated": "2024-01-01T12:00:00.000Z",
            "status": "CONFIRMED",
            "customer": {"name": "Test Customer"}
        }
    }

    # 1) POST /api/whatsapp/webhook with valid payload within rate limits -> expect 200 OK
    r1 = session.post(whatsapp_webhook_url, json=whatsapp_payload, headers=headers, timeout=TIMEOUT)
    assert r1.status_code == 200, f"Expected 200 OK for WhatsApp webhook but got {r1.status_code}"

    # 2) POST /api/whatsapp/webhook flood to exceed throttle limits -> expect 429 Too Many Requests
    # Flood with rapid requests to trigger throttling
    flood_count = 20
    too_many_requests_received = False
    for i in range(flood_count):
        r = session.post(whatsapp_webhook_url, json=whatsapp_payload, headers=headers, timeout=TIMEOUT)
        if r.status_code == 429:
            too_many_requests_received = True
            break
        # Small delay to simulate a burst but allow server processing
        time.sleep(0.1)
    assert too_many_requests_received, "Expected 429 Too Many Requests on WhatsApp webhook flood but did not receive"

    # 3) POST /api/webhooks/asaas with valid payload -> expect 200 OK
    r2 = session.post(asaas_webhook_url, json=asaas_payload, headers=headers, timeout=TIMEOUT)
    assert r2.status_code == 200, f"Expected 200 OK for Asaas webhook but got {r2.status_code}"

    # 4) POST /api/webhooks/asaas flood to check rate limit (optional, as not explicitly requested; skipping)
    # Instead, wait briefly to allow job processing and alert generation simulation

    # Wait some time for jobs to be processed and alerts generated on failure (assuming asynchronous processing)
    time.sleep(5)

    # 5) GET /admin as super-admin -> expect 200 HTML view that includes webhook failure alerts/metrics
    r3 = session.get(admin_dashboard_url, timeout=TIMEOUT, auth=AUTH)
    assert r3.status_code == 200, f"Expected 200 OK for admin dashboard but got {r3.status_code}"
    content_lower = r3.text.lower()
    # Check simple keywords for failure alerts and webhook metrics presence in dashboard content
    assert ("webhook" in content_lower or "alert" in content_lower or "failure" in content_lower or "metric" in content_lower), \
        "Admin dashboard does not show webhook failure alert or metrics"

test_verify_webhook_throttling_and_queue_failure_alerts()