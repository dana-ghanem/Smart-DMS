"""
Python API for Text Preprocessing
Provides a simple interface to call preprocessing from Laravel
"""

import json
import sys
from text_preprocessing import TextPreprocessor


def process_text(text, remove_stopwords=True, lemmatize=True):
    """
    Process text and return results as JSON.
    
    Args:
        text (str): Text to process
        remove_stopwords (bool): Remove stopwords
        lemmatize (bool): Lemmatize tokens
        
    Returns:
        dict: Processing results
    """
    try:
        preprocessor = TextPreprocessor()
        
        # Get tokens
        tokens = preprocessor.preprocess(
            text, 
            remove_stopwords=remove_stopwords,
            lemmatize=lemmatize
        )
        
        # Get cleaned text
        cleaned_text = preprocessor.preprocess(
            text,
            remove_stopwords=remove_stopwords,
            lemmatize=lemmatize,
            return_string=True
        )
        
        return {
            'success': True,
            'tokens': tokens,
            'token_count': len(tokens),
            'cleaned_text': cleaned_text,
            'text_length': len(text)
        }
    except Exception as e:
        return {
            'success': False,
            'error': str(e)
        }


def main():
    """Main entry point for CLI usage."""
    if len(sys.argv) > 1:
        text = sys.argv[1]
        remove_stopwords = sys.argv[2].lower() == 'true' if len(sys.argv) > 2 else True
        lemmatize = sys.argv[3].lower() == 'true' if len(sys.argv) > 3 else True
        
        result = process_text(text, remove_stopwords, lemmatize)
        print(json.dumps(result))
    else:
        print(json.dumps({
            'success': False,
            'error': 'No text provided'
        }))


if __name__ == "__main__":
    main()
