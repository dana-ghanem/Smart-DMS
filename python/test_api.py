"""
Test Suite for Smart DMS AI API
Student B - API Testing & Validation

Run this file to test all API endpoints:
    python python/test_api.py
"""

import requests
import json
import time
from typing import Dict, Any

# API Base URL
BASE_URL = "http://localhost:8000"

# Colors for terminal output
class Colors:
    HEADER = '\033[95m'
    OKBLUE = '\033[94m'
    OKCYAN = '\033[96m'
    OKGREEN = '\033[92m'
    WARNING = '\033[93m'
    FAIL = '\033[91m'
    ENDC = '\033[0m'
    BOLD = '\033[1m'
    UNDERLINE = '\033[4m'


class APITester:
    """API Testing class"""
    
    def __init__(self, base_url: str = BASE_URL):
        self.base_url = base_url
        self.passed = 0
        self.failed = 0
        
    def print_header(self, text: str):
        """Print section header"""
        print(f"\n{Colors.HEADER}{Colors.BOLD}{'=' * 60}")
        print(f"{text.center(60)}")
        print(f"{'=' * 60}{Colors.ENDC}\n")
    
    def print_test(self, test_name: str):
        """Print test name"""
        print(f"{Colors.OKBLUE}▶ Testing: {test_name}{Colors.ENDC}")
    
    def print_success(self, message: str):
        """Print success message"""
        print(f"{Colors.OKGREEN}✓ {message}{Colors.ENDC}")
        self.passed += 1
    
    def print_error(self, message: str):
        """Print error message"""
        print(f"{Colors.FAIL}✗ {message}{Colors.ENDC}")
        self.failed += 1
    
    def print_info(self, message: str):
        """Print info message"""
        print(f"{Colors.OKCYAN}ℹ {message}{Colors.ENDC}")
    
    def print_response(self, response: Dict[Any, Any]):
        """Pretty print JSON response"""
        print(f"{Colors.OKCYAN}{json.dumps(response, indent=2)}{Colors.ENDC}")
    
    def test_endpoint(self, method: str, endpoint: str, **kwargs) -> requests.Response:
        """Test an API endpoint"""
        url = f"{self.base_url}{endpoint}"
        try:
            if method.upper() == "GET":
                response = requests.get(url, **kwargs)
            elif method.upper() == "POST":
                response = requests.post(url, **kwargs)
            else:
                raise ValueError(f"Unsupported method: {method}")
            return response
        except requests.exceptions.ConnectionError:
            self.print_error(f"Could not connect to {url}")
            self.print_info("Make sure the API server is running: python python\\main.py")
            raise
    
    # ────────────────────────────────────────────────────────────────
    # Health & Status Tests
    # ────────────────────────────────────────────────────────────────
    
    def test_health(self):
        """Test health check endpoint"""
        self.print_test("Health Check")
        response = self.test_endpoint("GET", "/health")
        
        if response.status_code == 200:
            data = response.json()
            if data.get("status") == "healthy":
                self.print_success("Health check passed")
                self.print_response(data)
            else:
                self.print_error("API not healthy")
        else:
            self.print_error(f"Status code: {response.status_code}")
    
    def test_status(self):
        """Test status endpoint"""
        self.print_test("Status Information")
        response = self.test_endpoint("GET", "/status")
        
        if response.status_code == 200:
            data = response.json()
            self.print_success("Status endpoint working")
            self.print_response(data)
        else:
            self.print_error(f"Status code: {response.status_code}")
    
    def test_info(self):
        """Test info endpoint"""
        self.print_test("API Information")
        response = self.test_endpoint("GET", "/info")
        
        if response.status_code == 200:
            data = response.json()
            self.print_success("Info endpoint working")
            self.print_info(f"API Version: {data.get('version')}")
            self.print_info(f"Available endpoints: {len(data.get('endpoints', {}))}")
        else:
            self.print_error(f"Status code: {response.status_code}")
    
    # ────────────────────────────────────────────────────────────────
    # Text Preprocessing Tests
    # ────────────────────────────────────────────────────────────────
    
    def test_preprocess_basic(self):
        """Test basic text preprocessing"""
        self.print_test("Basic Text Preprocessing")
        
        payload = {
            "text": "The quick brown fox jumps over the lazy dog",
            "remove_stopwords": True,
            "lemmatize": True
        }
        
        response = self.test_endpoint(
            "POST",
            "/preprocess",
            json=payload,
            headers={"Content-Type": "application/json"}
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get("success"):
                self.print_success("Text preprocessing successful")
                self.print_info(f"Tokens: {data.get('tokens')}")
                self.print_info(f"Token count: {data.get('token_count')}")
            else:
                self.print_error("Preprocessing failed")
        else:
            self.print_error(f"Status code: {response.status_code}")
    
    def test_preprocess_without_stopwords(self):
        """Test preprocessing without stopword removal"""
        self.print_test("Preprocessing Without Stopword Removal")
        
        payload = {
            "text": "The quick brown fox",
            "remove_stopwords": False,
            "lemmatize": True
        }
        
        response = self.test_endpoint(
            "POST",
            "/preprocess",
            json=payload,
            headers={"Content-Type": "application/json"}
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get("success"):
                self.print_success("Preprocessing without stopwords successful")
                self.print_info(f"Tokens: {data.get('tokens')}")
            else:
                self.print_error("Preprocessing failed")
        else:
            self.print_error(f"Status code: {response.status_code}")
    
    def test_batch_preprocess(self):
        """Test batch text preprocessing"""
        self.print_test("Batch Text Preprocessing")
        
        texts = [
            "First document about machine learning",
            "Second document about neural networks",
            "Third document about artificial intelligence"
        ]
        
        response = self.test_endpoint(
            "POST",
            "/preprocess/batch",
            json=texts,
            headers={"Content-Type": "application/json"}
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get("success"):
                self.print_success(f"Batch preprocessing successful - {data.get('total_processed')} items")
                self.print_info(f"Results: {json.dumps(data.get('results', [])[:1], indent=2)}")
            else:
                self.print_error("Batch preprocessing failed")
        else:
            self.print_error(f"Status code: {response.status_code}")
    
    # ────────────────────────────────────────────────────────────────
    # Document Search Tests
    # ────────────────────────────────────────────────────────────────
    
    def test_search_post(self):
        """Test document search (POST method)"""
        self.print_test("Document Search (POST)")
        
        payload = {
            "query": "machine learning",
            "top_k": 5,
            "min_score": 0.0
        }
        
        response = self.test_endpoint(
            "POST",
            "/search",
            json=payload,
            headers={"Content-Type": "application/json"}
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get("success"):
                self.print_success("Search successful")
                self.print_info(f"Query: {data.get('query')}")
                self.print_info(f"Results found: {data.get('total_results')}")
                if data.get("results"):
                    self.print_response(data.get("results", [])[:1])
            else:
                self.print_error(f"Search failed: {data.get('error', 'Unknown error')}")
        else:
            self.print_error(f"Status code: {response.status_code}")
    
    def test_search_get(self):
        """Test document search (GET method)"""
        self.print_test("Document Search (GET)")
        
        params = {
            "query": "artificial intelligence",
            "top_k": 5,
            "min_score": 0.1
        }
        
        response = self.test_endpoint("GET", "/search", params=params)
        
        if response.status_code == 200:
            data = response.json()
            if data.get("success"):
                self.print_success("GET search successful")
                self.print_info(f"Results: {data.get('total_results')}")
            else:
                self.print_error("Search failed")
        else:
            self.print_error(f"Status code: {response.status_code}")
    
    # ────────────────────────────────────────────────────────────────
    # Query Analysis Tests
    # ────────────────────────────────────────────────────────────────
    
    def test_analyze_query(self):
        """Test query analysis"""
        self.print_test("Query Analysis")
        
        payload = {
            "query": "information retrieval system",
            "enable_fuzzy": True,
            "enable_expansion": True
        }
        
        response = self.test_endpoint(
            "POST",
            "/analyze-query",
            json=payload,
            headers={"Content-Type": "application/json"}
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get("success"):
                self.print_success("Query analysis successful")
                self.print_info(f"Original query: {data.get('query')}")
            else:
                self.print_error("Query analysis failed")
        else:
            self.print_error(f"Status code: {response.status_code}")
    
    def test_query_suggestions(self):
        """Test query suggestions"""
        self.print_test("Query Suggestions")
        
        response = self.test_endpoint(
            "GET",
            "/query-suggestions",
            params={"query": "neural networks"}
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get("success"):
                self.print_success("Query suggestions working")
                self.print_info(f"Original query: {data.get('original_query')}")
            else:
                self.print_error("Failed to get suggestions")
        else:
            self.print_error(f"Status code: {response.status_code}")
    
    # ────────────────────────────────────────────────────────────────
    # Combined Operation Tests
    # ────────────────────────────────────────────────────────────────
    
    def test_search_and_preprocess(self):
        """Test combined search and preprocess operation"""
        self.print_test("Search and Preprocess (Combined)")
        
        payload = {
            "query": "deep learning",
            "top_k": 5,
            "min_score": 0.0
        }
        
        response = self.test_endpoint(
            "POST",
            "/search-and-preprocess",
            json=payload,
            headers={"Content-Type": "application/json"}
        )
        
        if response.status_code == 200:
            data = response.json()
            if data.get("success"):
                self.print_success("Combined operation successful")
                self.print_info(f"Original query: {data.get('original_query')}")
                self.print_info(f"Preprocessed tokens: {data.get('preprocessed_query_tokens')}")
                self.print_info(f"Search results: {data.get('total_results')}")
            else:
                self.print_error("Combined operation failed")
        else:
            self.print_error(f"Status code: {response.status_code}")
    
    # ────────────────────────────────────────────────────────────────
    # Error Handling Tests
    # ────────────────────────────────────────────────────────────────
    
    def test_error_empty_text(self):
        """Test error handling for empty text"""
        self.print_test("Error Handling: Empty Text")
        
        payload = {
            "text": "",
            "remove_stopwords": True,
            "lemmatize": True
        }
        
        response = self.test_endpoint(
            "POST",
            "/preprocess",
            json=payload,
            headers={"Content-Type": "application/json"}
        )
        
        if response.status_code != 200:
            self.print_success("Error handling working - rejected empty text")
        else:
            self.print_error("Should have rejected empty text")
    
    def test_error_invalid_json(self):
        """Test error handling for invalid JSON"""
        self.print_test("Error Handling: Invalid JSON")
        
        response = self.test_endpoint(
            "POST",
            "/preprocess",
            data="invalid json",
            headers={"Content-Type": "application/json"}
        )
        
        if response.status_code != 200:
            self.print_success("Error handling working - rejected invalid JSON")
        else:
            self.print_error("Should have rejected invalid JSON")
    
    # ────────────────────────────────────────────────────────────────
    # Main Test Runner
    # ────────────────────────────────────────────────────────────────
    
    def run_all_tests(self):
        """Run all tests"""
        self.print_header("Smart DMS AI API - Test Suite")
        self.print_info("Testing all API endpoints")
        
        try:
            # Health & Status
            self.print_header("Health & Status Tests")
            self.test_health()
            self.test_status()
            self.test_info()
            
            # Text Preprocessing
            self.print_header("Text Preprocessing Tests")
            self.test_preprocess_basic()
            self.test_preprocess_without_stopwords()
            self.test_batch_preprocess()
            
            # Document Search
            self.print_header("Document Search Tests")
            self.test_search_post()
            self.test_search_get()
            
            # Query Analysis
            self.print_header("Query Analysis Tests")
            self.test_analyze_query()
            self.test_query_suggestions()
            
            # Combined Operations
            self.print_header("Combined Operation Tests")
            self.test_search_and_preprocess()
            
            # Error Handling
            self.print_header("Error Handling Tests")
            self.test_error_empty_text()
            self.test_error_invalid_json()
            
            # Summary
            self.print_summary()
            
        except requests.exceptions.ConnectionError as e:
            self.print_error(f"Connection error: {str(e)}")
            self.print_info("\nMake sure the API server is running:")
            self.print_info("  Windows: .\\start_api.ps1")
            self.print_info("  Linux/Mac: bash start_api.sh")
            self.print_info("  Or manually: cd python && uvicorn main:app --reload")
    
    def print_summary(self):
        """Print test summary"""
        self.print_header("Test Summary")
        total = self.passed + self.failed
        
        print(f"{Colors.OKGREEN}Passed: {self.passed}/{total}{Colors.ENDC}")
        if self.failed > 0:
            print(f"{Colors.FAIL}Failed: {self.failed}/{total}{Colors.ENDC}")
        
        if self.failed == 0:
            print(f"\n{Colors.OKGREEN}{Colors.BOLD}All tests passed! ✓{Colors.ENDC}")
        else:
            print(f"\n{Colors.FAIL}{Colors.BOLD}Some tests failed ✗{Colors.ENDC}")
        
        success_rate = (self.passed / total * 100) if total > 0 else 0
        print(f"\nSuccess Rate: {success_rate:.1f}%\n")


def main():
    """Main entry point"""
    tester = APITester()
    tester.run_all_tests()


if __name__ == "__main__":
    main()
