<?php

it('health endpoint returns ok status with database and cache checks', function () {
    $response = $this->getJson('/health');

    $response->assertStatus(200)
             ->assertJsonStructure(['status', 'timestamp', 'version', 'checks'])
             ->assertJsonPath('status', 'ok')
             ->assertJsonPath('checks.database', 'ok')
             ->assertJsonPath('checks.cache', 'ok');
});

it('ping endpoint returns ok', function () {
    $response = $this->getJson('/ping');

    $response->assertOk()
             ->assertJsonPath('status', 'ok')
             ->assertJsonStructure(['status', 'ts']);
});

it('health endpoint is publicly accessible without authentication', function () {
    $response = $this->getJson('/health');

    // Should not redirect to login
    $response->assertStatus(200);
});
