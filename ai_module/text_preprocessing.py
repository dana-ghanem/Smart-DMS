"""
Text Preprocessing Module
Handles tokenization, stopwords removal, and other text cleaning operations.
"""

import re
import nltk
from nltk.tokenize import word_tokenize, sent_tokenize
from nltk.corpus import stopwords
from nltk.stem import WordNetLemmatizer
import string

# Download required NLTK data
try:
    nltk.data.find('tokenizers/punkt')
except LookupError:
    nltk.download('punkt')

try:
    nltk.data.find('tokenizers/punkt_tab')
except LookupError:
    nltk.download('punkt_tab')

try:
    nltk.data.find('corpora/stopwords')
except LookupError:
    nltk.download('stopwords')

try:
    nltk.data.find('corpora/wordnet')
except LookupError:
    nltk.download('wordnet')
    nltk.download('omw-1.4')


class TextPreprocessor:
    """
    A comprehensive text preprocessing class.
    Handles tokenization, stopwords removal, lemmatization, and text cleaning.
    """

    def __init__(self, language='english', remove_punctuation=True, lowercase=True):
        self.language           = language
        self.lowercase          = lowercase
        self._remove_punctuation = remove_punctuation   # renamed to avoid clash with method
        self.stop_words         = set(stopwords.words(language))
        self.lemmatizer         = WordNetLemmatizer()

    def clean_text(self, text):
        text = re.sub(r'<[^>]+>', '', text)
        text = re.sub(r'http\S+|www\S+', '', text)
        text = re.sub(r'\S+@\S+', '', text)
        text = re.sub(r'\s+', ' ', text).strip()
        if self.lowercase:
            text = text.lower()
        return text

    def tokenize_words(self, text):
        return word_tokenize(text)

    def tokenize_sentences(self, text):
        return sent_tokenize(text)

    def remove_stopwords(self, tokens):
        return [t for t in tokens if t.lower() not in self.stop_words]

    def remove_punctuation_tokens(self, tokens):
        """Remove punctuation tokens from list."""
        return [t for t in tokens if t not in string.punctuation]

    def lemmatize(self, tokens):
        return [self.lemmatizer.lemmatize(t) for t in tokens]

    def preprocess(self, text, remove_stopwords=True, lemmatize=True, return_string=False):
        # Step 1: Clean
        cleaned = self.clean_text(text)

        # Step 2: Tokenize
        tokens = self.tokenize_words(cleaned)

        # Step 3: Remove punctuation
        if self._remove_punctuation:
            tokens = self.remove_punctuation_tokens(tokens)

        # Step 4: Remove stopwords
        if remove_stopwords:
            tokens = self.remove_stopwords(tokens)

        # Step 5: Lemmatize
        if lemmatize:
            tokens = self.lemmatize(tokens)

        # Step 6: Remove empty tokens
        tokens = [t for t in tokens if t.strip()]

        if return_string:
            return ' '.join(tokens)
        return tokens

    def get_stopwords(self):
        return self.stop_words

    def add_stopwords(self, words):
        if isinstance(words, str):
            self.stop_words.add(words)
        else:
            self.stop_words.update(words)

    def remove_custom_words(self, tokens, custom_words):
        custom_lower = set(w.lower() for w in custom_words)
        return [t for t in tokens if t.lower() not in custom_lower]


# Example usage
if __name__ == "__main__":
    preprocessor = TextPreprocessor()
    sample = "Hello! This is a sample text. Visit https://example.com or email info@example.com"

    print("Original:", sample)
    print("Cleaned: ", preprocessor.clean_text(sample))
    print("Tokens:  ", preprocessor.preprocess(sample))
    print("String:  ", preprocessor.preprocess(sample, return_string=True))
