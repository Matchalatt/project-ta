import sys
import pandas as pd
import pymysql
import json
import numpy as np
import warnings

# Abaikan UserWarning dari pandas terkait SQLAlchemy
warnings.filterwarnings("ignore", category=UserWarning, module='pandas')

# Import library yang dibutuhkan
from gensim.models import Word2Vec
from nltk.tokenize import word_tokenize
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity

# Import library untuk visualisasi (heatmap)
import matplotlib.pyplot as plt
import seaborn as sns

def create_hybrid_vector(text, w2v_model, tfidf_vectorizer, tfidf_matrix_row, vector_size):
    """Membuat vektor hibrida (TF-IDF Weighted Word2Vec) untuk sebuah teks."""
    tokens = word_tokenize(text.lower())
    
    feature_names = tfidf_vectorizer.get_feature_names_out()
    word_to_tfidf_score = {word: tfidf_matrix_row[0, tfidf_vectorizer.vocabulary_[word]] 
                           for word in tokens if word in tfidf_vectorizer.vocabulary_}

    weighted_vectors = []
    for word in tokens:
        if word in w2v_model.wv and word in word_to_tfidf_score:
            word_vector = w2v_model.wv[word]
            tfidf_score = word_to_tfidf_score[word]
            weighted_vectors.append(word_vector * tfidf_score)

    if not weighted_vectors:
        return np.zeros(vector_size)
    
    return np.mean(weighted_vectors, axis=0)

def visualize_matrix(matrix, labels, title='Cosine Similarity Matrix'):
    """Membuat dan menyimpan heatmap dari matriks kemiripan."""
    if len(labels) > 50:
        # Menggunakan sys.stderr.write agar tidak mengganggu output JSON utama
        sys.stderr.write("\n[Info] Jumlah produk > 50, heatmap tidak akan ditampilkan.\n")
        return

    plt.figure(figsize=(15, 12))
    sns.heatmap(matrix, annot=False, cmap='viridis', xticklabels=labels, yticklabels=labels)
    plt.title(title, fontsize=16)
    plt.xticks(rotation=90, fontsize=8)
    plt.yticks(rotation=0, fontsize=8)
    plt.tight_layout()
    
    filename = f"{title.replace(' ', '_').lower()}.png"
    plt.savefig(filename)
    # Menggunakan sys.stderr.write untuk semua output non-JSON
    sys.stderr.write(f"\nHeatmap telah disimpan ke file '{filename}'\n")

def get_recommendations(product_id, num_recommendations=5):
    db_config = {
        'host': '127.0.0.1', 'user': 'root', 'password': '',
        'db': 'snackjuara', 'charset': 'utf8mb4'
    }

    try:
        connection = pymysql.connect(**db_config)
        query = "SELECT id, name, tags, description FROM products"
        df = pd.read_sql(query, connection)
    except Exception as e:
        print(json.dumps({"error": f"Database connection failed: {e}"}))
        return
    finally:
        if 'connection' in locals() and connection.open:
            connection.close()

    if df.empty:
        print(json.dumps({"error": "No data returned."}))
        return

    df['tags'] = df['tags'].fillna('')
    df['description'] = df['description'].fillna('')
    df['combined_text'] = df['name'] + ' ' + df['tags'] + ' ' + df['description']

    tfidf_vectorizer = TfidfVectorizer()
    tfidf_matrix = tfidf_vectorizer.fit_transform(df['combined_text'])

    tokenized_text = [word_tokenize(text.lower()) for text in df['combined_text']]
    vector_size = 100
    w2v_model = Word2Vec(sentences=tokenized_text, vector_size=vector_size, window=5, min_count=1, workers=4)
    w2v_model.train(tokenized_text, total_examples=len(tokenized_text), epochs=20)

    hybrid_vectors = []
    for i in range(len(df)):
        text = df['combined_text'].iloc[i]
        tfidf_row = tfidf_matrix[i]
        hybrid_vec = create_hybrid_vector(text, w2v_model, tfidf_vectorizer, tfidf_row, vector_size)
        hybrid_vectors.append(hybrid_vec)
        
    vector_matrix = np.array(hybrid_vectors)
    cosine_sim = cosine_similarity(vector_matrix)

    # --- [MODIFIKASI] Panggil Fungsi Visualisasi ---
    visualize_matrix(cosine_sim, df['name'].tolist(), title='Heatmap Kemiripan Produk (Hybrid)')

    try:
        idx = df.index[df['id'] == product_id].tolist()[0]
    except IndexError:
        print(json.dumps({"error": "Product ID not found."}))
        return

    sim_scores = sorted(list(enumerate(cosine_sim[idx])), key=lambda x: x[1], reverse=True)
    
    # --- [MODIFIKASI] Tampilkan Data Analisis di Terminal ---
    # Menggunakan sys.stderr.write agar tidak tercampur dengan output JSON utama ke PHP
    analysis_output = "\n--- HASIL ANALISIS HYBRID (TF-IDF * Word2Vec) ---\n"
    target_name = df.iloc[idx]['name']
    analysis_output += f"Produk Target: {target_name} (ID: {product_id})\n"
    analysis_output += f"Bentuk Matriks Cosine Sim: {cosine_sim.shape}\n"
    analysis_output += "\nTop 5 Skor Kemiripan:\n"
    
    top_scores = sim_scores[1:num_recommendations+1]
    for i, score in top_scores:
        product_name = df.iloc[i]['name']
        analysis_output += f"  - Produk: {product_name:<25} | Skor: {score:.4f}\n"
    analysis_output += "--------------------------------\n"
    
    sys.stderr.write(analysis_output)
    # --------------------------------------------------------------------

    product_indices = [i[0] for i in top_scores]
    recommended_product_ids = df['id'].iloc[product_indices].tolist()
    
    # --- PENTING: Output final untuk PHP harus hanya JSON ini ---
    print(json.dumps(recommended_product_ids))

if __name__ == "__main__":
    if len(sys.argv) > 1:
        try:
            target_product_id = int(sys.argv[1])
            get_recommendations(target_product_id)
        except ValueError:
            print(json.dumps({"error": "Invalid Product ID provided."}))
    else:
        print(json.dumps({"error": "No Product ID provided."}))