# File: recommend_visualize.py (Versi Word2Vec + Visualisasi)
import sys
import pandas as pd
import pymysql
import json
import numpy as np
from gensim.models import Word2Vec
from nltk.tokenize import word_tokenize
from sklearn.metrics.pairwise import cosine_similarity
import matplotlib.pyplot as plt
import seaborn as sns

def get_vector(text, model, vector_size):
    tokens = word_tokenize(text.lower())
    vectors = [model.wv[word] for word in tokens if word in model.wv]
    if not vectors:
        return np.zeros(vector_size)
    return np.mean(vectors, axis=0)

def visualize_matrix(matrix, labels, title='Cosine Similarity Matrix'):
    """Membuat dan menyimpan heatmap dari matriks kemiripan."""
    # Batasi jumlah label jika terlalu banyak agar visualisasi tidak terlalu padat
    if len(labels) > 50:
        print("\n[Info] Jumlah produk > 50, heatmap tidak akan ditampilkan untuk menjaga keterbacaan.")
        return

    plt.figure(figsize=(15, 12))
    sns.heatmap(matrix, annot=False, cmap='viridis', xticklabels=labels, yticklabels=labels)
    plt.title(title, fontsize=16)
    plt.xticks(rotation=90, fontsize=8)
    plt.yticks(rotation=0, fontsize=8)
    plt.tight_layout()
    
    filename = f"{title.replace(' ', '_').lower()}.png"
    plt.savefig(filename)
    print(f"\nHeatmap telah disimpan ke file '{filename}'")

def get_recommendations(product_id, num_recommendations=5):
    db_config = {
        'host': '127.0.0.1', 'user': 'root', 'password': '',
        'db': 'snackjuara', 'charset': 'utf8mb4'
    }
    df = pd.DataFrame()
    try:
        connection = pymysql.connect(**db_config)
        query = "SELECT id, name, tags FROM products"
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
    tokenized_tags = [word_tokenize(tags.lower()) for tags in df['tags']]
    vector_size = 100
    model = Word2Vec(sentences=tokenized_tags, vector_size=vector_size, window=5, min_count=1, workers=4)
    model.train(tokenized_tags, total_examples=len(tokenized_tags), epochs=20)
    
    product_vectors = df['tags'].apply(lambda x: get_vector(x, model, vector_size))
    vector_matrix = np.vstack(product_vectors)
    
    cosine_sim = cosine_similarity(vector_matrix, vector_matrix)

    # PANGGIL FUNGSI VISUALISASI DI SINI
    product_names = df['name'].tolist()
    visualize_matrix(cosine_sim, product_names, title='Heatmap Kemiripan Produk (Word2Vec)')

    try:
        idx = df.index[df['id'] == product_id].tolist()[0]
    except IndexError:
        print(json.dumps({"error": "Product ID not found."}))
        return

    sim_scores = sorted(list(enumerate(cosine_sim[idx])), key=lambda x: x[1], reverse=True)
    
    print("\n--- HASIL ANALISIS WORD2VEC ---")
    target_name = df.iloc[idx]['name']
    print(f"Produk Target: {target_name} (ID: {product_id})")
    print(f"Bentuk Matriks Cosine Sim: {cosine_sim.shape}")
    print("\nTop 5 Skor Kemiripan:")
    top_scores = sim_scores[1:num_recommendations+1]
    for i, score in top_scores:
        product_name = df.iloc[i]['name']
        print(f"  - Produk: {product_name:<20} | Skor: {score:.4f}")
    print("--------------------------------\n")
    
    product_indices = [i[0] for i in top_scores]
    recommended_product_ids = df['id'].iloc[product_indices].tolist()
    
    print(json.dumps(recommended_product_ids))

if __name__ == "__main__":
    if len(sys.argv) > 1:
        try:
            target_product_id = int(sys.argv[1])
            get_recommendations(target_product_id)
        except ValueError:
            print(json.dumps({"error": "Invalid Product ID."}))
    else:
        print(json.dumps({"error": "No Product ID provided."}))