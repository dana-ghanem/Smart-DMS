"""
Configuration settings for the FastAPI application
"""

from pydantic_settings import BaseSettings
from typing import List


class Settings(BaseSettings):
    """Application settings"""
    
    # API Configuration
    api_title: str = "Smart DMS AI API"
    api_version: str = "1.0.0"
    api_host: str = "0.0.0.0"
    api_port: int = 8000
    api_reload: bool = True
    api_workers: int = 4
    
    # Document Configuration
    documents_folder: str = "ai_module/documents"
    
    # Search Configuration
    max_search_results: int = 50
    default_top_k: int = 10
    min_score_threshold: float = 0.0
    
    # Batch Processing
    max_batch_size: int = 100
    
    # CORS Configuration
    cors_origins: List[str] = [
        "http://localhost:8000",
        "http://localhost:3000",
        "http://localhost:5173",
        "*"
    ]
    cors_credentials: bool = True
    cors_methods: List[str] = ["*"]
    cors_headers: List[str] = ["*"]
    
    # Text Preprocessing Defaults
    default_remove_stopwords: bool = True
    default_lemmatize: bool = True
    
    # Query Processing Defaults
    default_enable_fuzzy: bool = True
    default_enable_expansion: bool = True
    
    # Logging
    log_level: str = "INFO"
    
    class Config:
        env_file = ".env"
        case_sensitive = False


# Global settings instance
settings = Settings()
