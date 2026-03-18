print("Hello from VS Code!")

# test_nlp.py
import nltk
import spacy

# --- NLTK setup ---
nltk.download('punkt')          # Tokenizer models
from nltk.tokenize import word_tokenize, sent_tokenize

# --- spaCy setup ---
nlp = spacy.load("en_core_web_sm")

# Sample text
text = "Hello! I am learning Natural Language Processing. It's fascinating, isn't it?"

# ----- NLTK processing -----
print("--- NLTK Results ---")
words_nltk = word_tokenize(text)
sentences_nltk = sent_tokenize(text)
print("Words:", words_nltk)
print("Sentences:", sentences_nltk)

# ----- spaCy processing -----
print("\n--- spaCy Results ---")
doc = nlp(text)
words_spacy = [token.text for token in doc]
sentences_spacy = [sent.text for sent in doc.sents]
print("Words:", words_spacy)
print("Sentences:", sentences_spacy)