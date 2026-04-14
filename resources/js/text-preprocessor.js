/**
 * Text Preprocessing API Helper
 * Adapted to match existing Laravel Route: api/preprocess/document/{id}
 */
export default class TextPreprocessor {
    constructor() {
        this.apiBaseUrl = '/api';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    /**
     * Maps the nested AI response to a flat format for your UI
     */
    mapResponse(data) {
        // If the backend returns data inside 'processing' (as seen in your Postman)
        if (data.success && data.processing) {
            const proc = data.processing;
            return {
                success: true,
                tokens: proc.tokens || [],
                token_count: proc.metadata?.token_count || 0,
                cleaned_text: proc.metadata?.processed_query || "",
                text_length: proc.metadata?.original_query?.length || 0
            };
        }

        // Pass through error information
        if (!data.success) {
            return {
                success: false,
                error: data.error || 'Unknown error',
                document_id: data.document_id,
                document_title: data.document_title
            };
        }

        return data;
    }

    async preprocessText(text, options = {}) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/preprocess`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                },
                body: JSON.stringify({
                    text: text,
                    remove_stopwords: options.removeStopwords ?? true,
                    lemmatize: options.lemmatize ?? true
                })
            });

            const data = await response.json();
            return this.mapResponse(data);
        } catch (error) {
            return { success: false, error: error.message };
        }
    }

    /**
     * Matches route: api/preprocess/document/{id}
     */
    async analyzeDocument(documentId) {
        try {
            // Changed to GET and updated URL structure to match your error message
            const response = await fetch(`${this.apiBaseUrl}/preprocess/document/${documentId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || `Route not found: ${response.status}`);
            }

            return this.mapResponse(data);
        } catch (error) {
            console.error("Document Analysis Error:", error);
            return { success: false, error: error.message };
        }
    }
}
