import os
import logging
from pathlib import Path
from sklearn.feature_extraction.text import TfidfVectorizer
from document_reader import read_document

logging.basicConfig(
    level=logging.INFO,
    format="%(asctime)s  [%(levelname)s]  %(name)s — %(message)s",
    datefmt="%Y-%m-%d %H:%M:%S",
)
logger = logging.getLogger("tfidf_search")

# Folder containing documents
DOC_FOLDER = Path("documents")

# Read all supported documents
docs = []
file_names = []

for file_path in DOC_FOLDER.iterdir():
    if file_path.suffix.lower() in {".txt", ".pdf", ".docx"}:
        result = read_document(str(file_path))
        if result["success"]:
            docs.append(result["cleaned_text"] or result["raw_text"])
            file_names.append(result["file_name"])
        else:
            logger.warning("Skipped %s: %s", file_path.name, result.get("error"))

if not docs:
    logger.error("❌ No valid documents found. Exiting.")
    exit()

# Create TF-IDF matrix
vectorizer = TfidfVectorizer(stop_words="english")
tfidf_matrix = vectorizer.fit_transform(docs)
logger.info("TF-IDF matrix shape: %s", tfidf_matrix.shape)

# Interactive search
while True:
    keyword = input("\nEnter keyword to search (or 'exit'): ").strip()
    if keyword.lower() == "exit":
        break
    if not keyword:
        continue

    if keyword not in vectorizer.vocabulary_:
        print("❌ Keyword not found in any document.")
        continue

    # Get TF-IDF scores for this keyword
    idx = vectorizer.vocabulary_[keyword]
    scores = tfidf_matrix[:, idx].toarray().flatten()

    # Rank documents
    ranked = sorted(
        zip(file_names, scores),
        key=lambda x: x[1],
        reverse=True
    )

    print("\nTop results:")
    for fname, score in ranked:
        if score > 0:
            print(f"  {fname}  (score: {score:.4f})")