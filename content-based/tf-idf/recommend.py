# File: recommend.py (Versi TF-IDF - Dimodifikasi untuk Analisis)
import sys
import pandas as pd
import pymysql
import json
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import matplotlib.pyplot as plt
import seaborn as sns

def visualize_matrix(matrix, labels, title='Cosine Similarity Matrix'):
    """Membuat dan menyimpan heatmap dari matriks kemiripan."""
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
    # --- 1. Konfigurasi & Koneksi Database ---
    db_config = {
        'host': '127.0.0.1',
        'user': 'root', # Ganti dengan username db Anda
        'password': '', # Ganti dengan password db Anda
        'db': 'snackjuara', # Ganti dengan nama db Anda
        'charset': 'utf8mb4'
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
        print(json.dumps({"error": "No data returned from the database."}))
        return
        
    # --- 3. Pra-pemrosesan Data ---
    df['tags'] = df['tags'].fillna('')

    # --- 4. Kalkulasi TF-IDF ---
    tfidf = TfidfVectorizer()
    tfidf_matrix = tfidf.fit_transform(df['tags'])

    # --- 5. Kalkulasi Cosine Similarity ---
    cosine_sim = cosine_similarity(tfidf_matrix, tfidf_matrix)
    
    # --- [MODIFIKASI] Panggil Fungsi Visualisasi ---
    product_names = df['name'].tolist()
    visualize_matrix(cosine_sim, product_names, title='Heatmap Kemiripan Produk (TF-IDF)')

    # --- 6. Dapatkan Rekomendasi ---
    try:
        idx = df.index[df['id'] == product_id].tolist()[0]
    except IndexError:
        print(json.dumps({"error": "Product ID not found in DataFrame."}))
        return

    sim_scores = list(enumerate(cosine_sim[idx]))
    sim_scores = sorted(sim_scores, key=lambda x: x[1], reverse=True)
    
    # --- [MODIFIKASI] Tampilkan Data Analisis Sebelum Hasil Akhir ---
    print("\n--- HASIL ANALISIS TF-IDF ---")
    target_name = df.iloc[idx]['name']
    print(f"Produk Target: {target_name} (ID: {product_id})")
    print(f"Bentuk Matriks Cosine Sim: {cosine_sim.shape}")
    print("\nTop 5 Skor Kemiripan:")
    # Ambil 5 teratas, lewati item pertama (dirinya sendiri)
    top_scores = sim_scores[1:num_recommendations+1]
    for i, score in top_scores:
        product_name = df.iloc[i]['name']
        print(f"  - Produk: {product_name:<20} | Skor: {score:.4f}")
    print("--------------------------------\n")
    # --------------------------------------------------------------------

    # Lanjutkan proses untuk mendapatkan ID produk
    product_indices = [i[0] for i in top_scores]
    recommended_product_ids = df['id'].iloc[product_indices].tolist()

    # --- 7. Kembalikan Hasil dalam Format JSON ---
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