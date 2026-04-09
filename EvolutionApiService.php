// Increase HTTP timeout from 120 to 300 seconds for instance creation 
// Existing code: $this->httpClient->setTimeout(120);
$this->httpClient->setTimeout(300);