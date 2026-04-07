/**
 * Text Preprocessing API Helper
 * Handles communication with the Laravel backend preprocessing endpoints
 */

class TextPreprocessor {
    constructor() {
        this.apiBaseUrl = '/api';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    /**
     * Preprocess text via API
     * @param {string} text - Text to preprocess
     * @param {object} options - Options object
     * @param {boolean} options.removeStopwords - Remove stopwords (default: true)
     * @param {boolean} options.lemmatize - Lemmatize tokens (default: true)
     * @returns {Promise<object>} Processing results
     */
    async preprocessText(text, options = {}) {
        const {
            removeStopwords = true,
            lemmatize = true
        } = options;

        try {
            console.log('Starting preprocessing...', {text: text.substring(0, 50)});
            console.log('CSRF Token:', this.csrfToken);
            
            const response = await fetch(`${this.apiBaseUrl}/preprocess`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    text: text,
                    remove_stopwords: removeStopwords,
                    lemmatize: lemmatize
                })
            });

            console.log('Response status:', response.status);
            const data = await response.json();
            console.log('Response data:', data);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return data;
        } catch (error) {
            console.error('Preprocessing error:', error);
            return {
                success: false,
                error: error.message || 'Unknown error occurred'
            };
        }
    }

    /**
     * Analyze a document
     * @param {number} documentId - Document ID to analyze
     * @returns {Promise<object>} Analysis results
     */
    async analyzeDocument(documentId) {
        try {
            console.log('Analyzing document:', documentId);
            
            const response = await fetch(`${this.apiBaseUrl}/analyze-document`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    document_id: documentId
                })
            });

            console.log('Document analysis response status:', response.status);
            const data = await response.json();
            console.log('Document analysis data:', data);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            return data;
        } catch (error) {
            console.error('Document analysis error:', error);
            return {
                success: false,
                error: error.message || 'Failed to analyze document'
            };
        }
    }

    /**
     * Display preprocessing results in HTML
     * @param {object} results - Results from preprocessing
     * @param {string} containerId - ID of container to display results in
     */
    displayResults(results, containerId) {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (!results.success) {
            container.innerHTML = `<div class="alert alert-danger">${results.error}</div>`;
            return;
        }

        const html = `
            <div class="preprocessing-results">
                <div class="row">
                    <div class="col-md-6">
                        <h5>Statistics</h5>
                        <p><strong>Original Length:</strong> ${results.text_length} characters</p>
                        <p><strong>Tokens Found:</strong> ${results.token_count}</p>
                    </div>
                    <div class="col-md-6">
                        <h5>Cleaned Text</h5>
                        <p>${results.cleaned_text}</p>
                    </div>
                </div>
                <h5 class="mt-3">Tokens</h5>
                <div class="tokens-list">
                    ${results.tokens.map(token => 
                        `<span class="badge bg-info">${token}</span>`
                    ).join('')}
                </div>
            </div>
        `;

        container.innerHTML = html;
    }
}

// Export for use
window.TextPreprocessor = TextPreprocessor;
