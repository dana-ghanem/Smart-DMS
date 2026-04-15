import os
from pathlib import Path
from typing import Any, Dict, List, Optional

import requests


def _load_local_env() -> None:
    """Load simple KEY=VALUE pairs from the repo .env into process env once."""
    env_path = Path(__file__).resolve().parent.parent / ".env"

    if not env_path.exists():
        return

    for raw_line in env_path.read_text(encoding="utf-8").splitlines():
        line = raw_line.strip()
        if not line or line.startswith("#") or "=" not in line:
            continue

        key, value = line.split("=", 1)
        key = key.strip()
        value = value.strip().strip("\"'")

        if key and key not in os.environ:
            os.environ[key] = value


_load_local_env()


class LaravelApiClient:
    """
    Small Laravel API client that auto-authenticates with Sanctum token login.

    Expected env vars:
      - LARAVEL_URL or APP_URL
      - LARAVEL_API_EMAIL
      - LARAVEL_API_PASSWORD
    """

    def __init__(
        self,
        base_url: Optional[str] = None,
        email: Optional[str] = None,
        password: Optional[str] = None,
        timeout: int = 15,
    ) -> None:
        self.base_url = (base_url or os.getenv("LARAVEL_URL") or os.getenv("APP_URL") or "http://localhost:8001").rstrip("/")
        self.email = email or os.getenv("LARAVEL_API_EMAIL")
        self.password = password or os.getenv("LARAVEL_API_PASSWORD")
        self.timeout = timeout
        self.session = requests.Session()
        self._token: Optional[str] = None

    def _login(self) -> str:
        if not self.email or not self.password:
            raise RuntimeError(
                "Missing Laravel API credentials. Set LARAVEL_API_EMAIL and LARAVEL_API_PASSWORD in your environment or .env."
            )

        response = self.session.post(
            f"{self.base_url}/api/auth/login",
            json={"email": self.email, "password": self.password},
            timeout=self.timeout,
        )
        response.raise_for_status()

        payload = response.json()
        token = payload.get("token")
        if not token:
            raise RuntimeError("Laravel login succeeded but no API token was returned.")

        self._token = token
        self.session.headers.update({"Authorization": f"Bearer {token}"})
        return token

    def ensure_authenticated(self) -> None:
        if not self._token:
            self._login()

    def request(self, method: str, path: str, retry_on_401: bool = True, **kwargs: Any) -> requests.Response:
        self.ensure_authenticated()
        timeout = kwargs.pop("timeout", self.timeout)

        response = self.session.request(
            method=method,
            url=f"{self.base_url}{path}",
            timeout=timeout,
            **kwargs,
        )

        if response.status_code == 401 and retry_on_401:
            self._login()
            response = self.session.request(
                method=method,
                url=f"{self.base_url}{path}",
                timeout=timeout,
                **kwargs,
            )

        response.raise_for_status()
        return response

    def get_json(self, path: str, **kwargs: Any) -> Dict[str, Any]:
        return self.request("GET", path, **kwargs).json()

    def post_json(self, path: str, payload: Optional[Dict[str, Any]] = None, **kwargs: Any) -> Dict[str, Any]:
        return self.request("POST", path, json=payload or {}, **kwargs).json()

    def put_json(self, path: str, payload: Optional[Dict[str, Any]] = None, **kwargs: Any) -> Dict[str, Any]:
        return self.request("PUT", path, json=payload or {}, **kwargs).json()

    def delete_json(self, path: str, **kwargs: Any) -> Dict[str, Any]:
        return self.request("DELETE", path, **kwargs).json()

    def list_documents(self) -> List[Dict[str, Any]]:
        payload = self.get_json("/api/documents")
        return payload.get("data") or payload.get("documents") or []

    def get_document(self, document_id: int) -> Dict[str, Any]:
        payload = self.get_json(f"/api/documents/{document_id}")
        return payload.get("data") or payload.get("document") or {}

    def logout(self) -> Optional[Dict[str, Any]]:
        if not self._token:
            return None
        payload = self.post_json("/api/auth/logout")
        self._token = None
        self.session.headers.pop("Authorization", None)
        return payload
