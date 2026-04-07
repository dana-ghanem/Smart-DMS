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
    nltk.data.find('corpora/stopwords')
except LookupError:
    nltk.download('stopwords')

try:
    nltk.data.find('corpora/wordnet')
except LookupError:
    nltk.download('wordnet')


class TextPreprocessor:
    """
    A comprehensive text preprocessing class.
    Handles tokenization, stopwords removal, lemmatization, and text cleaning.
    """
    
    def __init__(self, language='english', remove_punctuation=True, lowercase=True):
        """
        Initialize the TextPreprocessor.
        
        Args:
            language (str): Language for stopwords (default: 'english')
            remove_punctuation (bool): Whether to remove punctuation (default: True)
            lowercase (bool): Whether to convert text to lowercase (default: True)
        """
        self.language = language
        self.remove_punctuation = remove_punctuation
        self.lowercase = lowercase
        self.stop_words = set(stopwords.words(language))
        self.lemmatizer = WordNetLemmatizer()
    
    def clean_text(self, text):
        """
        Clean text by handling HTML entities, extra whitespace, etc.
        
        Args:
            text (str): Raw text to clean
            
        Returns:
            str: Cleaned text
        """
        # Remove HTML tags
        text = re.sub(r'<[^>]+>', '', text)
        
        # Remove URLs
        text = re.sub(r'http\S+|www\S+', '', text)
        
        # Remove email addresses
        text = re.sub(r'\S+@\S+', '', text)
        
        # Remove extra whitespace
        text = re.sub(r'\s+', ' ', text).strip()
        
        # Convert to lowercase if enabled
        if self.lowercase:
            text = text.lower()
        
        return text
    
    def tokenize_words(self, text):
        """
        Tokenize text into words.
        
        Args:
            text (str): Text to tokenize
            
        Returns:
            list: List of word tokens
        """
        tokens = word_tokenize(text)
        return tokens
    
    def tokenize_sentences(self, text):
        """
        Tokenize text into sentences.
        
        Args:
            text (str): Text to tokenize
            
        Returns:
            list: List of sentence tokens
        """
        sentences = sent_tokenize(text)
        return sentences
    
    def remove_stopwords(self, tokens):
        """
        Remove stopwords from token list.
        
        Args:
            tokens (list): List of tokens
            
        Returns:
            list: Tokens with stopwords removed
        """
        return [token for token in tokens if token.lower() not in self.stop_words]
    
    def remove_punctuation(self, tokens):
        """
        Remove punctuation from tokens.
        
        Args:
            tokens (list): List of tokens
            
        Returns:
            list: Tokens without punctuation
        """
        return [token for token in tokens if token not in string.punctuation]
    
    def lemmatize(self, tokens):
        """
        Lemmatize tokens.
        
        Args:
            tokens (list): List of tokens
            
        Returns:
            list: Lemmatized tokens
        """
        return [self.lemmatizer.lemmatize(token) for token in tokens]
    
    def preprocess(self, text, remove_stopwords=True, lemmatize=True, return_string=False):
        """
        Complete text preprocessing pipeline.
        
        Args:
            text (str): Raw text to preprocess
            remove_stopwords (bool): Whether to remove stopwords (default: True)
            lemmatize (bool): Whether to lemmatize tokens (default: True)
            return_string (bool): Whether to return as string (default: False)
            
        Returns:
            list or str: Processed tokens or string
        """
        # Step 1: Clean text
        cleaned_text = self.clean_text(text)
        
        # Step 2: Tokenize into words
        tokens = self.tokenize_words(cleaned_text)
        
        # Step 3: Remove punctuation
        if self.remove_punctuation:
            tokens = self.remove_punctuation(tokens)
        
        # Step 4: Remove stopwords
        if remove_stopwords:
            tokens = self.remove_stopwords(tokens)
        
        # Step 5: Lemmatize
        if lemmatize:
            tokens = self.lemmatize(tokens)
        
        # Step 6: Remove empty tokens
        tokens = [token for token in tokens if token.strip()]
        
        # Return as string or list
        if return_string:
            return ' '.join(tokens)
        return tokens
    
    def get_stopwords(self):
        """
        Get the current stopwords set.
        
        Returns:
            set: Set of stopwords
        """
        return self.stop_words
    
    def add_stopwords(self, words):
        """
        Add custom stopwords.
        
        Args:
            words (list or str): Word(s) to add to stopwords
        """
        if isinstance(words, str):
            self.stop_words.add(words)
        else:
            self.stop_words.update(words)
    
    def remove_custom_words(self, tokens, custom_words):
        """
        Remove custom words from tokens.
        
        Args:
            tokens (list): List of tokens
            custom_words (list): Words to remove
            
        Returns:
            list: Filtered tokens
        """
        custom_words_lower = set(word.lower() for word in custom_words)
        return [token for token in tokens if token.lower() not in custom_words_lower]


# Example usage
if __name__ == "__main__":
    # Initialize preprocessor
    preprocessor = TextPreprocessor(language='english')
    
    # Sample text
    sample_text = """
    Hello! This is a sample text for preprocessing. 
    It contains multiple sentences. We will tokenize, remove stopwords, 
    and lemmatize the text. Visit https://example.com for more info.
    Contact us at info@example.com
    """
    
    print("=" * 60)
    print("ORIGINAL TEXT:")
    print("=" * 60)
    print(sample_text)
    
    print("\n" + "=" * 60)
    print("CLEANED TEXT:")
    print("=" * 60)
    cleaned = preprocessor.clean_text(sample_text)
    print(cleaned)
    
    print("\n" + "=" * 60)
    print("WORD TOKENIZATION:")
    print("=" * 60)
    words = preprocessor.tokenize_words(cleaned)
    print(words)
    
    print("\n" + "=" * 60)
    print("SENTENCE TOKENIZATION:")
    print("=" * 60)
    sentences = preprocessor.tokenize_sentences(cleaned)
    for i, sent in enumerate(sentences, 1):
        print(f"{i}. {sent}")
    
    print("\n" + "=" * 60)
    print("COMPLETE PREPROCESSING PIPELINE:")
    print("=" * 60)
    processed_tokens = preprocessor.preprocess(sample_text)
    print("Tokens:", processed_tokens)
    print("Count:", len(processed_tokens))
    
    print("\n" + "=" * 60)
    print("AS STRING:")
    print("=" * 60)
    processed_string = preprocessor.preprocess(sample_text, return_string=True)
    print(processed_string)
