import sys
import pandas as pd
import pymysql
import json
import warnings
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.metrics.pairwise import cosine_similarity

# Mengabaikan warning agar output di terminal bersih
warnings.filterwarnings("ignore")

# =====================================================================
# 1. KONFIGURASI DATABASE
# =====================================================================
DB_CONFIG = {
    'host': '127.0.0.1', 
    'user': 'root', 
    'password': '',
    'db': 'snackjuara', 
    'charset': 'utf8mb4'
}

# =====================================================================
# 2. CLASS: CONTENT BASED RECOMMENDER (OPTIMIZED)
# =====================================================================
class ContentRecommender:
    def __init__(self):
        self.df = pd.DataFrame()
        self.cosine_sim = None
        self.indices = None
        # Otomatis memuat data saat object dibuat
        self._load_and_process_data()

    def _load_and_process_data(self):
        """
        Memuat data dari database dan membangun matriks TF-IDF & Cosine Similarity.
        Dijalankan hanya SATU KALI untuk menghemat waktu.
        """
        connection = None
        try:
            connection = pymysql.connect(**DB_CONFIG)
            query = "SELECT id, name, tags FROM products"
            self.df = pd.read_sql(query, connection)
            
            if self.df.empty:
                return

            # --- Preprocessing ---
            # Mengisi nilai null dengan string kosong
            self.df['tags'] = self.df['tags'].fillna('').astype(str)
            
            # --- TF-IDF Vectorization ---
            # stop_words='english' bisa dihapus jika produkmu dominan bahasa Indonesia
            tfidf = TfidfVectorizer(stop_words='english') 
            tfidf_matrix = tfidf.fit_transform(self.df['tags'])
            
            # --- Cosine Similarity ---
            # Menggunakan cosine_similarity sesuai laporan skripsi
            self.cosine_sim = cosine_similarity(tfidf_matrix, tfidf_matrix)
            
            # --- Indexing ---
            # Membuat mapping dari ID Produk ke Index DataFrame untuk pencarian cepat
            self.indices = pd.Series(self.df.index, index=self.df['id']).drop_duplicates()
            
        except Exception as e:
            # Print error ke stderr agar tidak merusak format JSON di stdout
            sys.stderr.write(f"[ERROR] Init ContentRecommender: {e}\n")
        finally:
            if connection and connection.open:
                connection.close()

    def get_recs(self, product_id, k=5):
        """
        Fungsi utama untuk mendapatkan rekomendasi.
        """
        # Cek validitas data
        if self.cosine_sim is None or product_id not in self.indices:
            return []

        # Ambil index dataframe dari product_id yang diminta
        idx = self.indices[product_id]

        # Ambil skor similaritas dari matriks
        # list(enumerate(...)) menghasilkan pasangan (index_produk, skor)
        sim_scores = list(enumerate(self.cosine_sim[idx]))

        # Urutkan berdasarkan skor tertinggi (descending)
        sim_scores = sorted(sim_scores, key=lambda x: x[1], reverse=True)

        # Ambil top K rekomendasi
        # Mulai dari index 1 karena index 0 adalah produk itu sendiri (skor 1.0)
        sim_scores = sim_scores[1:k+1]

        # Ambil Product ID dari hasil index tadi
        product_indices = [i[0] for i in sim_scores]
        
        # Kembalikan dalam bentuk List ID
        return self.df['id'].iloc[product_indices].tolist()

# =====================================================================
# 3. GLOBAL INSTANCE & WRAPPER FUNCTION
# =====================================================================

# Variabel global untuk menyimpan instance model agar tidak diload ulang
_recommender_instance = None

def get_recommendations(product_id, num_recommendations=5, verbose=False):
    """
    Wrapper function yang bisa dipanggil dari file lain.
    Menggunakan pola Singleton untuk efisiensi.
    """
    global _recommender_instance
    
    # Cek apakah instance sudah ada? Jika belum, buat baru.
    # Jika sudah ada, langsung pakai yang lama (CACHE HIT).
    if _recommender_instance is None:
        _recommender_instance = ContentRecommender()
    
    return _recommender_instance.get_recs(product_id, k=num_recommendations)

# =====================================================================
# 4. MAIN ENTRY POINT (CLI SUPPORT)
# =====================================================================
if __name__ == "__main__":
    # Blok ini dijalankan jika script dipanggil via terminal/PHP/Laravel
    # Contoh: python recommend_tfidf.py 15
    if len(sys.argv) > 1:
        try:
            pid = int(sys.argv[1])
            # Panggil fungsi rekomendasi
            res = get_recommendations(pid)
            # Output JSON murni untuk ditangkap aplikasi lain
            print(json.dumps(res))
        except ValueError:
            # Jika input bukan angka
            print(json.dumps([]))
        except Exception as e:
            # Error lain
            sys.stderr.write(f"Error: {e}")
            print(json.dumps([]))
    else:
        # Jika dipanggil tanpa argumen
        print(json.dumps([]))