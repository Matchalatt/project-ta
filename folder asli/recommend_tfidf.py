import sys
import pandas as pd
import pymysql
import json
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity
import matplotlib.pyplot as plt
import seaborn as sns
import warnings

# Mengabaikan UserWarning dari pandas terkait SQLAlchemy agar output lebih bersih
warnings.filterwarnings("ignore", category=UserWarning, module='pandas')

def visualize_matrix(matrix, labels, title='Cosine Similarity Matrix'):
    """Membuat dan menyimpan heatmap dari matriks kemiripan."""
    # Batasi jumlah label jika terlalu banyak agar visualisasi tidak terlalu padat
    if len(labels) > 50:
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
    sys.stderr.write(f"\nHeatmap telah disimpan ke file '{filename}'\n")

def get_recommendations(product_id, num_recommendations=5, verbose=True):
    """
    Menghasilkan rekomendasi produk berdasarkan kemiripan konten (TF-IDF).

    Args:
        product_id (int): ID dari produk yang menjadi acuan.
        num_recommendations (int): Jumlah rekomendasi yang diinginkan.
        verbose (bool): Jika True, akan mencetak detail analisis dan membuat heatmap.

    Returns:
        list: Daftar ID produk yang direkomendasikan.
              Mengembalikan None jika terjadi error.
    """
    # --- 1. Konfigurasi & Koneksi Database ---
    db_config = {
        'host': '127.0.0.1',
        'user': 'root',
        'password': '',
        'db': 'snackjuara',
        'charset': 'utf8mb4'
    }

    try:
        connection = pymysql.connect(**db_config)
        query = "SELECT id, name, tags FROM products"
        df = pd.read_sql(query, connection)
    except Exception as e:
        if verbose:
            print(json.dumps({"error": f"Database connection failed: {e}"}))
        return None
    finally:
        if 'connection' in locals() and connection.open:
            connection.close()

    if df.empty:
        if verbose:
            print(json.dumps({"error": "No data returned from the database."}))
        return None
        
    # --- 2. Pra-pemrosesan Data ---
    df['tags'] = df['tags'].fillna('')

    # --- 3. Kalkulasi TF-IDF & Cosine Similarity ---
    tfidf = TfidfVectorizer()
    tfidf_matrix = tfidf.fit_transform(df['tags'])
    cosine_sim = cosine_similarity(tfidf_matrix, tfidf_matrix)
    
    # --- 4. Dapatkan Indeks Produk Target ---
    try:
        idx = df.index[df['id'] == product_id].tolist()[0]
    except IndexError:
        if verbose:
            print(json.dumps({"error": "Product ID not found in DataFrame."}))
        return None

    # --- 5. Visualisasi & Analisis (Jika verbose=True) ---
    if verbose:
        visualize_matrix(cosine_sim, df['name'].tolist(), title='Heatmap Kemiripan Produk (TF-IDF)')
        
        sys.stderr.write("\n--- HASIL ANALISIS TF-IDF ---\n")
        target_name = df.iloc[idx]['name']
        sys.stderr.write(f"Produk Target: {target_name} (ID: {product_id})\n")
        sys.stderr.write(f"Bentuk Matriks Cosine Sim: {cosine_sim.shape}\n")
        sys.stderr.write("\nTop 5 Skor Kemiripan:\n")

    # --- 6. Urutkan dan Ambil Rekomendasi Teratas ---
    sim_scores = list(enumerate(cosine_sim[idx]))
    sim_scores = sorted(sim_scores, key=lambda x: x[1], reverse=True)
    top_scores = sim_scores[1:num_recommendations+1]
    
    if verbose:
        for i, score in top_scores:
            product_name = df.iloc[i]['name']
            sys.stderr.write(f"  - Produk: {product_name:<20} | Skor: {score:.4f}\n")
        sys.stderr.write("--------------------------------\n")

    # --- 7. Kembalikan Hasil ---
    product_indices = [i[0] for i in top_scores]
    recommended_product_ids = df['id'].iloc[product_indices].tolist()
    
    return recommended_product_ids


if __name__ == "__main__":
    # Blok ini hanya akan berjalan jika file dieksekusi secara langsung
    # dari command line, contoh: python recommend_tfidf.py 15
    if len(sys.argv) > 1:
        try:
            target_product_id = int(sys.argv[1])
            # Panggil fungsi dengan mode verbose=True (default)
            recommendations = get_recommendations(target_product_id)
            
            # Jika fungsi berhasil, cetak hasilnya sebagai JSON
            if recommendations is not None:
                print(json.dumps(recommendations))
                
        except ValueError:
            print(json.dumps({"error": "Invalid Product ID provided."}))
    else:
        print(json.dumps({"error": "No Product ID provided."}))