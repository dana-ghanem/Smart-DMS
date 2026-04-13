import math

# 🔹 Student A
from tfidf_search import tfidf_matrix, vectorizer, file_names

# 🔹 Student B
from query_processing import QueryProcessor


# 🔹 Convert matrix → vectors
doc_vectors = tfidf_matrix.toarray()


# 🔹 Cosine similarity
def cosine_similarity(vec1, vec2):
    dot_product = sum(a*b for a, b in zip(vec1, vec2))
    
    norm1 = math.sqrt(sum(a*a for a in vec1))
    norm2 = math.sqrt(sum(b*b for b in vec2))
    
    if norm1 == 0 or norm2 == 0:
        return 0
    
    return dot_product / (norm1 * norm2)


# 🔹 Ranking
def rank_documents(query_vector, doc_vectors):
    scores = []
    
    for i, doc_vec in enumerate(doc_vectors):
        score = cosine_similarity(query_vector, doc_vec)
        scores.append((i, score))
    
    scores.sort(key=lambda x: x[1], reverse=True)
    return scores


# 🔹 MAIN
if __name__ == "__main__":
    
    processor = QueryProcessor()

    # 🔥 VERY IMPORTANT
    processor.set_vectorizer(vectorizer)

    query = input("Enter your query: ")

    result = processor.process_query(query)

    if result["success"] and result.get("vector"):
        query_vector = result["vector"]

        results = rank_documents(query_vector, doc_vectors)

        print("\n🔍 Results:\n")

        for doc_id, score in results:
            if score > 0:
                print(f"{file_names[doc_id]} → Score: {score}")

    else:
        print("❌ Error:", result.get("error"))