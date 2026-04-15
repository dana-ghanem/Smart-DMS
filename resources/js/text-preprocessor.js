export default class TextPreprocessor {
    constructor() {
        this.apiBaseUrl = '/ui-api';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    mapResponse(data) {
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

        if (data.success && data.tokens) {
            return {
                success: true,
                tokens: data.tokens || [],
                token_count: data.token_count || 0,
                cleaned_text: data.cleaned_text || "",
                text_length: data.text_length || 0
            };
        }

        if (!data.success) {
            return {
                success: false,
                error: this.extractError(data),
                document_id: data.document_id,
                document_title: data.document_title
            };
        }

        return data;
    }

    extractError(data) {
        if (!data) {
            return 'Unknown error';
        }

        if (typeof data === 'string') {
            return data;
        }

        if (data.error) {
            return data.error;
        }

        if (data.detail) {
            return data.detail;
        }

        if (data.message) {
            return data.message;
        }

        if (data.errors) {
            if (typeof data.errors === 'string') {
                return data.errors;
            }
            if (typeof data.errors === 'object') {
                return Object.values(data.errors).flat().join(' ');
            }
        }

        return 'Unknown error';
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
                credentials: 'include',
                body: JSON.stringify({
                    text: text,
                    remove_stopwords: options.removeStopwords ?? true,
                    lemmatize: options.lemmatize ?? true
                })
            });

            const data = await response.json();
            if (!response.ok) {
                return { success: false, error: this.extractError(data) };
            }
            return this.mapResponse(data);
        } catch (error) {
            return { success: false, error: error.message };
        }
    }

    async analyzeDocument(documentId) {
        try {
            const response = await fetch(`${this.apiBaseUrl}/preprocess/document/${documentId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'include'
            });

            const data = await response.json();

            if (!response.ok) {
                return { success: false, error: this.extractError(data) };
            }

            return this.mapResponse(data);
        } catch (error) {
            console.error("Document Analysis Error:", error);
            return { success: false, error: error.message };
        }
    }
}

// Make available globally for blade scripts
window.TextPreprocessor = TextPreprocessor;
