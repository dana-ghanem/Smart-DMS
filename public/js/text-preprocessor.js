// public/js/text-preprocessor.js

class TextPreprocessor {

    constructor() {
        this.apiUrl = '/api/preprocess';
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    }

    async preprocessText(text, options = {}) {
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    text: text,
                    remove_stopwords: options.removeStopwords ?? true,
                    lemmatize: options.lemmatize ?? true,
                })
            });

            const data = await response.json();

            if (!response.ok) {
                return {
                    success: false,
                    error: data.message || 'Server error: ' + response.status
                };
            }

            return {
                success: true,
                text_length:  data.text_length  ?? data.original_length ?? text.length,
                token_count:  data.token_count  ?? (data.tokens?.length ?? 0),
                cleaned_text: data.cleaned_text ?? '',
                tokens:       data.tokens       ?? [],
            };

        } catch (error) {
            console.error('TextPreprocessor error:', error);
            return {
                success: false,
                error: 'Could not connect to the server. Is the Python microservice running?'
            };
        }
    }

    async analyzeDocument(docId) {
        try {
            const response = await fetch(`/api/preprocess/document/${docId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json',
                }
            });

            const data = await response.json();

            if (!response.ok) {
                return {
                    success: false,
                    error: data.message || 'Server error: ' + response.status
                };
            }

            return {
                success: true,
                text_length:  data.text_length  ?? data.original_length ?? 0,
                token_count:  data.token_count  ?? (data.tokens?.length ?? 0),
                cleaned_text: data.cleaned_text ?? '',
                tokens:       data.tokens       ?? [],
            };

        } catch (error) {
            console.error('TextPreprocessor analyzeDocument error:', error);
            return {
                success: false,
                error: 'Could not connect to the server. Is the Python microservice running?'
            };
        }
    }
}
