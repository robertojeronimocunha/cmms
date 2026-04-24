def test_ativos_sem_token_retorna_401(client):
    r = client.get("/api/v1/ativos")
    assert r.status_code == 401
